<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TestConnectionMail;
use App\Models\EmailLog;
use App\Models\Integrations\MailSetting;
use App\Services\Mail\MailSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

/**
 * Configuration SMTP + historique des envois — écran d'admin unique.
 *
 * Une seule ligne active en base ; l'écran laisse à découvert le fait qu'on
 * lit encore le .env (badge `.env` / `DB`) pour que le déploiement d'un
 * nouveau client soit visible et fasse basculer volontairement.
 */
class MailSettingsController extends Controller
{
    private const ENCRYPTIONS = ['tls', 'ssl', ''];

    public function __construct(private readonly MailSettingsService $service) {}

    public function index(): View
    {
        $setting  = MailSetting::current();
        $resolved = $this->service->resolved();

        return view('integrations.mail', [
            'setting'      => $setting,
            'resolved'     => $resolved,
            'source'       => $resolved['source'],   // 'db' | 'env'
            'has_password' => $setting && ! empty($setting->getRawOriginal('password')),
            'encryptions'  => self::ENCRYPTIONS,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'driver'            => 'required|string|in:smtp',
            'host'              => 'required|string|max:255',
            'port'              => 'required|integer|min:1|max:65535',
            'encryption'        => 'nullable|string|in:tls,ssl',
            'username'          => 'nullable|string|max:255',
            // Laissée vide → on garde le mot de passe existant.
            'password'          => 'nullable|string|max:255',
            'from_address'      => 'required|email|max:255',
            'from_name'         => 'required|string|max:255',
            'reply_to_use_user' => 'nullable|boolean',
            'timeout'           => 'required|integer|min:5|max:300',
            'is_active'         => 'nullable|boolean',
        ]);

        $setting = MailSetting::current() ?? new MailSetting();
        $setting->driver            = $validated['driver'];
        $setting->host              = $validated['host'];
        $setting->port              = $validated['port'];
        $setting->encryption        = $validated['encryption'] ?: null;
        $setting->username          = $validated['username'] ?: null;
        $setting->from_address      = $validated['from_address'];
        $setting->from_name         = $validated['from_name'];
        $setting->reply_to_use_user = (bool) ($validated['reply_to_use_user'] ?? false);
        $setting->timeout           = $validated['timeout'];
        $setting->is_active         = (bool) ($validated['is_active'] ?? true);

        if (! empty($validated['password'])) {
            $setting->password = $validated['password'];
        }

        $setting->save();

        $this->service->forget();
        $this->service->apply();

        return redirect()
            ->route('admin.integrations.mail.index')
            ->with('success', __('mail_settings.saved'));
    }

    /**
     * Envoi de test vers l'utilisateur connecté. Passe par le mailer résolu,
     * donc reflète exactement ce que le prochain envoi vraiment métier fera.
     * Le résultat est journalisé dans `email_logs` comme un envoi normal
     * (emailable = User connecté) pour rester traçable.
     */
    public function test(Request $request): JsonResponse
    {
        $to = $request->input('to') ?: Auth::user()?->email;
        if (! $to) {
            return response()->json(['ok' => false, 'message' => __('mail_settings.test_no_recipient')]);
        }

        // Applique la dernière config sauvegardée avant l'envoi (au cas où le
        // provider a démarré avant la première save).
        $this->service->forget();
        $this->service->apply();

        $log = new EmailLog([
            'to'              => $to,
            'subject'         => __('mail_settings.test_subject'),
            'message'         => __('mail_settings.test_body'),
            'status'          => 'pending',
            'sent_by_user_id' => Auth::id(),
        ]);
        // Attaché à l'utilisateur connecté — un test n'a pas de document.
        $log->emailable()->associate(Auth::user());
        $log->save();

        try {
            Mail::to($to)->send(new TestConnectionMail());
            $log->update(['status' => 'sent', 'sent_at' => now()]);

            return response()->json([
                'ok'      => true,
                'message' => __('mail_settings.test_sent', ['email' => $to]),
            ]);
        } catch (Throwable $e) {
            $log->update(['status' => 'failed', 'error' => $e->getMessage()]);

            return response()->json([
                'ok'      => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}

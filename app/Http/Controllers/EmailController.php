<?php

namespace App\Http\Controllers;

use App\Mail\DocumentMail;
use App\Models\Admin\EmailTemplate;
use App\Services\Documents\DocumentPdfService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EmailController extends Controller
{
    protected $models = [
        'order'              => \App\Models\Workflow\Orders::class,
        'quote'              => \App\Models\Workflow\Quotes::class,
        'delivery'           => \App\Models\Workflow\Deliverys::class,
        'invoice'            => \App\Models\Workflow\Invoices::class,
        'creditnote'         => \App\Models\Workflow\CreditNotes::class,
        'purchase'           => \App\Models\Purchases\Purchases::class,
        'purchase-quotation' => \App\Models\Purchases\PurchasesQuotation::class,
    ];

    public function __construct(private readonly DocumentPdfService $pdfService) {}

    public function create($type, $id)
    {
        if (! isset($this->models[$type])) {
            abort(404);
        }

        $model = $this->models[$type]::findOrFail($id);
        $this->guardSendable($type, $model);

        $contactMail = $model->contact->mail ?? null;
        $emailTemplate = EmailTemplate::where('document_type', $type)->first();

        $object = $emailTemplate
            ? $emailTemplate->subject . ' ' . $model->code
            : $model->code;

        session(['previous_url' => url()->previous()]);

        $pdfPreviewUrl = $this->pdfPreviewUrl($type, $model);

        return view('emails.create', compact('model', 'type', 'object', 'emailTemplate', 'contactMail', 'pdfPreviewUrl'));
    }

    /**
     * Envoi + traçabilité complète.
     *
     * Le log est écrit **avant** l'envoi en `pending`, puis mis à jour en
     * `sent` ou `failed`. Ainsi une erreur SMTP ne perd pas la tentative
     * (utile pour la reprise manuelle depuis l'écran des logs).
     */
    public function send(Request $request, $type, $id)
    {
        if (! isset($this->models[$type])) {
            abort(404);
        }

        $model = $this->models[$type]::findOrFail($id);
        $this->guardSendable($type, $model);

        $validated = $request->validate([
            'to'          => 'required|email',
            'subject'     => 'required|string',
            'message'     => 'required|string',
            'attachment'  => 'nullable|file|max:5120',
            'attach_pdf'  => 'nullable|boolean',
        ]);

        $manualPath = null;
        $manualName = null;
        if ($request->hasFile('attachment')) {
            $file       = $request->file('attachment');
            $manualPath = $file->store('attachments');
            $manualName = $file->getClientOriginalName();
        }

        // Génère le PDF avant l'envoi pour ne pas retenter à chaque échec SMTP.
        $pdfBytes    = null;
        $pdfFileName = null;
        if ($request->boolean('attach_pdf', true)) {
            try {
                $pdfBytes    = $this->pdfService->render($model);
                $pdfFileName = $this->pdfService->fileName($model);
            } catch (Throwable $e) {
                [$title, $items] = $this->splitPdfError($e->getMessage());

                return redirect()->back()
                    ->withInput()
                    ->with('pdfErrorTitle', $title)
                    ->with('pdfErrorItems', $items);
            }
        }

        $log = $model->emailLogs()->create([
            'to'                       => $validated['to'],
            'subject'                  => $validated['subject'],
            'message'                  => $validated['message'],
            'attachment'               => $manualPath,
            'attachment_original_name' => $manualName,
            'status'                   => 'pending',
            'sent_by_user_id'          => Auth::id(),
        ]);

        $user     = Auth::user();
        $fromAddr = (string) config('mail.from.address');
        $fromName = (string) config('mail.from.name');

        try {
            Mail::to($validated['to'])->send(new DocumentMail(
                document:            $model,
                subjectText:         $validated['subject'],
                messageContent:      $validated['message'],
                fromAddress:         $fromAddr,
                fromName:            $fromName,
                replyToAddress:      $user?->email,
                replyToName:         $user?->name,
                manualAttachmentPath: $manualPath,
                manualAttachmentName: $manualName,
                pdfBytes:            $pdfBytes,
                pdfFileName:         $pdfFileName,
            ));

            $log->update(['status' => 'sent', 'sent_at' => now()]);

            return redirect(session('previous_url', url('/')))
                ->with('success', __('general_content.email_sent_success_trans_key'));
        } catch (Throwable $e) {
            $log->update(['status' => 'failed', 'error' => $e->getMessage()]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['send' => __('general_content.email_send_failed_trans_key') . ' ' . $e->getMessage()]);
        }
    }

    /**
     * Empêche l'envoi tant que le document n'est pas émis.
     *
     * Un brouillon n'a pas de valeur légale et son PDF ne doit pas circuler
     * chez le destinataire — même règle que PrintController::getInvoicePdf().
     * On échoue avec un message explicite plutôt qu'une 403 nue pour que
     * l'utilisateur comprenne pourquoi le bouton ne marche pas.
     */
    private function guardSendable(string $type, $model): void
    {
        if ($type === 'invoice' && (int) $model->statu === 1) {
            abort(403, __('general_content.invoice_draft_no_email_trans_key'));
        }
    }

    /**
     * URL de rendu PDF réutilisée par la vue pour l'aperçu (iframe).
     * On passe par une route dédiée qui renvoie le PDF en `inline` — les routes
     * PrintController streamDownload en `attachment`, ce qui déclencherait un
     * téléchargement dans l'iframe au lieu d'un affichage.
     */
    private function pdfPreviewUrl(string $type, $model): ?string
    {
        if (! isset($this->models[$type])) {
            return null;
        }
        return route('email.preview-pdf', ['type' => $type, 'id' => $model->id]);
    }

    /**
     * Aperçu PDF servi inline — même moteur que l'auto-attach de l'envoi
     * (DocumentPdfService), donc l'aperçu montre exactement ce qui partira.
     *
     * Sur échec de génération (typiquement la validation Factur-X qui refuse
     * un SIREN mal formé), on rend une page HTML lisible dans l'iframe plutôt
     * que de laisser Whoops afficher une stack trace : l'utilisateur doit
     * comprendre ce qu'il a à corriger, pas voir du PHP.
     */
    public function previewPdf(string $type, int $id)
    {
        if (! isset($this->models[$type])) {
            abort(404);
        }

        $model = $this->models[$type]::findOrFail($id);
        $this->guardSendable($type, $model);

        try {
            $bytes    = $this->pdfService->render($model);
            $filename = $this->pdfService->fileName($model);

            return response($bytes, 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
                'Cache-Control'       => 'no-store, max-age=0',
            ]);
        } catch (Throwable $e) {
            [$title, $items] = $this->splitPdfError($e->getMessage());

            return response()
                ->view('emails.preview-error', [
                    'title' => $title,
                    'items' => $items,
                ], 200)
                ->header('X-Frame-Options', 'SAMEORIGIN');
        }
    }

    /**
     * Découpe le message multi-lignes de FacturXBuilder en titre + puces.
     * Les items commencent par « — » dans le RuntimeException, on les
     * détecte pour les afficher en liste.
     */
    private function splitPdfError(string $message): array
    {
        $lines = preg_split('/\r?\n/', trim($message));
        $title = array_shift($lines) ?: __('general_content.email_pdf_generation_failed_trans_key');
        // Nettoie les préfixes « — » (em-dash) laissés par le formateur.
        $items = array_values(array_filter(array_map(
            fn ($l) => ltrim(trim($l), "—- \t"),
            $lines,
        )));

        return [$title, $items];
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\DocumentMail;
use App\Models\EmailLog;
use App\Services\Documents\DocumentPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

/**
 * Historique global des envois d'email.
 *
 * L'écran par document est déjà porté par emails/create.blade ; celui-ci
 * couvre la vue transverse « qu'est-ce qui n'est pas parti aujourd'hui ? ».
 * Le renvoi ne re-génère pas le PDF depuis le log (les colonnes ne stockent
 * que l'entête) : on ré-attache seulement la pièce jointe manuelle
 * enregistrée dans storage. Un renvoi d'un log lié à un document reprend
 * l'auto-attach du PDF depuis le document original.
 */
class EmailLogsController extends Controller
{
    public function __construct(private readonly DocumentPdfService $pdfService) {}

    public function index(Request $request): View
    {
        $status = $request->input('status');
        $search = trim((string) $request->input('q'));

        $logs = EmailLog::query()
            ->with('sender')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('to', 'like', "%{$search}%")
                       ->orWhere('subject', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('admin.email-logs.index', [
            'logs'   => $logs,
            'status' => $status,
            'search' => $search,
            'counts' => [
                'pending' => EmailLog::where('status', 'pending')->count(),
                'sent'    => EmailLog::where('status', 'sent')->count(),
                'failed'  => EmailLog::where('status', 'failed')->count(),
            ],
        ]);
    }

    public function show(EmailLog $log): View
    {
        $log->load('sender', 'emailable');
        return view('admin.email-logs.show', ['log' => $log]);
    }

    public function resend(EmailLog $log)
    {
        $document = $log->emailable;
        if (! $document) {
            return back()->withErrors(['resend' => __('general_content.email_resend_orphan_trans_key')]);
        }

        $pdfBytes    = null;
        $pdfFileName = null;
        try {
            $pdfBytes    = $this->pdfService->render($document);
            $pdfFileName = $this->pdfService->fileName($document);
        } catch (Throwable) {
            // Renvoi de mail non-document (ex: test) — pas de PDF à attacher.
        }

        $user = Auth::user();
        try {
            Mail::to($log->to)->send(new DocumentMail(
                document:            $document,
                subjectText:         $log->subject,
                messageContent:      $log->message,
                fromAddress:         (string) config('mail.from.address'),
                fromName:            (string) config('mail.from.name'),
                replyToAddress:      $user?->email,
                replyToName:         $user?->name,
                manualAttachmentPath: $log->attachment,
                manualAttachmentName: $log->attachment_original_name,
                pdfBytes:            $pdfBytes,
                pdfFileName:         $pdfFileName,
            ));

            // Nouveau log pour tracer la re-tentative (on ne réécrit pas l'ancien).
            EmailLog::create([
                'emailable_type'           => $log->emailable_type,
                'emailable_id'             => $log->emailable_id,
                'to'                       => $log->to,
                'subject'                  => $log->subject,
                'message'                  => $log->message,
                'attachment'               => $log->attachment,
                'attachment_original_name' => $log->attachment_original_name,
                'status'                   => 'sent',
                'sent_at'                  => now(),
                'sent_by_user_id'          => Auth::id(),
            ]);

            return redirect()->route('admin.email-logs.index')
                ->with('success', __('general_content.email_resend_success_trans_key'));
        } catch (Throwable $e) {
            EmailLog::create([
                'emailable_type'  => $log->emailable_type,
                'emailable_id'    => $log->emailable_id,
                'to'              => $log->to,
                'subject'         => $log->subject,
                'message'         => $log->message,
                'status'          => 'failed',
                'error'           => $e->getMessage(),
                'sent_by_user_id' => Auth::id(),
            ]);

            return back()->withErrors(['resend' => $e->getMessage()]);
        }
    }
}

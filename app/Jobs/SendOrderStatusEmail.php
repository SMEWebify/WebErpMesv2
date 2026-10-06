<?php

namespace App\Jobs;

use App\Mail\OrderStatusChangedMail;
use App\Models\Workflow\Orders;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Prévient le contact client du passage de sa commande à une nouvelle étape.
 *
 * En file d'attente : un SMTP lent ou en panne ne doit jamais ralentir ni
 * faire échouer la mise à jour de la commande. Chaque tentative est tracée
 * dans email_logs (onglet historique des mails de la commande).
 */
class SendOrderStatusEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [60, 300];

    public function __construct(
        public readonly int $orderId,
        public readonly int $status,
        public readonly string $locale,
    ) {}

    public function handle(): void
    {
        $order = Orders::with(['companie', 'contact'])->find($this->orderId);

        // Re-vérifié à l'exécution : la commande a pu changer d'étape de nouveau,
        // ou le client désactiver l'option, entre le dispatch et le traitement.
        if (! $order || (int) $order->statu !== $this->status || ! $order->companie?->order_status_email) {
            return;
        }

        $to = $order->contact?->mail;
        if (! $to || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            Log::warning('Order status email skipped: no valid contact email', ['order_id' => $order->id]);
            return;
        }

        // Le worker tourne dans la locale par défaut : on rejoue celle de
        // l'utilisateur qui a changé l'étape, pour le mail comme pour le log.
        $mail    = (new OrderStatusChangedMail($order, $this->status))->locale($this->locale);
        $subject = $mail->withLocale($this->locale, fn () => $mail->subjectText());

        $log = $order->emailLogs()->create([
            'to'      => $to,
            'subject' => $subject,
            'message' => $subject,
            'status'  => 'pending',
        ]);

        try {
            Mail::to($to)->send($mail);
            $log->update(['status' => 'sent', 'sent_at' => now()]);
        } catch (Throwable $e) {
            $log->update(['status' => 'failed', 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}

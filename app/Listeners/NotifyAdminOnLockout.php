<?php

namespace App\Listeners;

use App\Models\Admin\Factory;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotifyAdminOnLockout implements ShouldQueue
{
    public function handle(Lockout $event): void
    {
        $request = $event->request;

        $email = (string) $request->input('email', '(inconnu)');
        $ip = (string) $request->ip();
        $userAgent = (string) $request->userAgent();
        $route = optional($request->route())->getName() ?? $request->path();

        $context = [
            'email' => $email,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'route' => $route,
            'at' => now()->toIso8601String(),
        ];

        // Trace systématique côté logs (utile même si mail KO)
        Log::warning('Login lockout triggered', $context);

        $recipient = optional(Factory::query()->first())->mail;

        if (! $recipient) {
            return;
        }

        Mail::raw(
            "Tentative de connexion répétée détectée et bloquée.\n\n".
            "Email: {$email}\n".
            "IP: {$ip}\n".
            "User-Agent: {$userAgent}\n".
            "Route: {$route}\n".
            "Heure: ".$context['at']."\n\n".
            "Si vous ne reconnaissez pas cette activité, vérifiez les comptes concernés et envisagez un blocage IP côté reverse proxy.",
            function ($message) use ($recipient, $email) {
                $message->to($recipient)
                    ->subject('[Sécurité] Lockout login détecté — '.$email);
            }
        );
    }
}

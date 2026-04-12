<?php

namespace App\Notifications\Concerns;

use Illuminate\Notifications\Messages\MailMessage;

trait HasMailFrom
{
    protected function applyFrom(MailMessage $message): MailMessage
    {
        $address = config('mail.from.address');
        $name    = config('mail.from.name');

        if (filled($address)) {
            $message->from($address, $name ?? null);
        }

        return $message;
    }
}

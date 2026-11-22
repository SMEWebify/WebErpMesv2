<?php

namespace App\Services\Mail;

use App\Models\User;
use App\Support\SafeHtml;

/**
 * Variables et signature des e-mails envoyés depuis l'ERP (documents, relances).
 *
 * Tout est remplacé à l'ouverture de l'écran d'envoi : l'utilisateur relit
 * dans l'éditeur exactement ce qui partira, et peut retoucher.
 *
 * Variables des documents : {code}, {libelle}, {client}, {contact},
 * {utilisateur}, {entreprise}, {signature}. Les relances ajoutent les leurs
 * (InvoiceReminderService) et passent par la même signature.
 */
class MailPersonalization
{
    public const SIGNATURE = '{signature}';

    /**
     * Valeurs brutes (non échappées) des variables d'un document.
     *
     * @return array<string, string>
     */
    public static function documentValues($document, ?User $user): array
    {
        $contact = $document->contact ?? null;

        return [
            '{code}'        => (string) ($document->code ?? ''),
            '{libelle}'     => (string) ($document->label ?? ''),
            '{client}'      => (string) ($document->companie?->label ?? ''),
            '{contact}'     => $contact
                ? trim(implode(' ', array_filter([$contact->civility, $contact->first_name, $contact->name])))
                : '',
            '{utilisateur}' => (string) ($user?->name ?? ''),
            '{entreprise}'  => (string) (app('Factory')->name ?? config('app.name')),
        ];
    }

    /**
     * Remplace les variables connues ; une variable inconnue reste telle quelle.
     * En HTML les valeurs sont échappées (nom de société saisi par l'utilisateur).
     *
     * @param array<string, string> $values
     */
    public static function replace(string $text, array $values, bool $html): string
    {
        if ($html) {
            $values = array_map(fn (string $value) => e($value), $values);
        }

        return strtr($text, $values);
    }

    /** Signature de l'utilisateur, nettoyée ; vide s'il n'en a pas. */
    public static function signature(?User $user): string
    {
        return trim(SafeHtml::clean($user?->email_signature));
    }

    /**
     * Pose la signature à la place de {signature}, sinon à la fin du message.
     * Sans signature, {signature} est simplement retiré : un modèle qui la
     * prévoit reste propre pour un utilisateur qui n'en a pas.
     */
    public static function withSignature(string $html, ?User $user): string
    {
        $signature = self::signature($user);

        if (str_contains($html, self::SIGNATURE)) {
            // Un paragraphe qui ne contient que la variable est remplacé en
            // entier : la signature porte ses propres paragraphes.
            $html = preg_replace('#<p>\s*'.preg_quote(self::SIGNATURE, '#').'\s*</p>#', $signature, $html);

            return str_replace(self::SIGNATURE, $signature, $html);
        }

        if ($signature === '') {
            return $html;
        }

        return $html.'<p><br></p>'.$signature;
    }
}

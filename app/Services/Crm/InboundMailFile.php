<?php

namespace App\Services\Crm;

use App\Services\Files\Preview\EmlParser;
use App\Services\Files\Preview\MsgParser;
use Illuminate\Http\UploadedFile;

/**
 * Lit un e-mail déposé (.eml ou .msg Outlook) pour en faire une demande entrante,
 * avec les lecteurs déjà utilisés par l'aperçu de la GED.
 */
class InboundMailFile
{
    /**
     * @return array{message: array<string, mixed>, attachments: array<int, array{name: string, mime: string, data: string}>, original: array{name: string, mime: string, data: string}}
     *
     * @throws \InvalidArgumentException  e-mail illisible ou sans expéditeur
     */
    public function read(UploadedFile $upload): array
    {
        $raw = (string) file_get_contents($upload->getRealPath());
        $extension = mb_strtolower($upload->getClientOriginalExtension());

        try {
            $parsed = $extension === 'msg' ? (new MsgParser())->parse($raw) : (new EmlParser())->parse($raw);
        } catch (\Throwable) {
            throw new \InvalidArgumentException('unreadable');
        }

        [$name, $email] = self::mailbox($parsed['from'] ?? null);
        if ($email === null) {
            throw new \InvalidArgumentException('no_sender');
        }

        return [
            'message' => [
                'subject'     => $parsed['subject'] ?? null,
                'from_email'  => $email,
                'from_name'   => $name,
                'received_at' => $parsed['date'] ?? null,
                'body'        => $parsed['text'] ?? self::textOf($parsed['html'] ?? null),
                'message_id'  => $extension === 'msg' ? null : self::messageIdHeader($raw),
            ],
            'attachments' => self::attachments($parsed['attachments'] ?? []),
            'original'    => [
                'name' => $upload->getClientOriginalName(),
                'mime' => $extension === 'msg' ? 'application/vnd.ms-outlook' : 'message/rfc822',
                'data' => $raw,
            ],
        ];
    }

    /**
     * « Nom <adresse> » ou adresse seule.
     *
     * @return array{0: ?string, 1: ?string}
     */
    public static function mailbox(?string $from): array
    {
        $from = trim((string) $from);

        if (preg_match('/^(.*)<\s*([^<>\s]+@[^<>\s]+)\s*>\s*$/u', $from, $m)) {
            $name = trim($m[1], " \t\"'");

            return [$name !== '' ? $name : null, mb_strtolower($m[2])];
        }

        if (filter_var($from, FILTER_VALIDATE_EMAIL)) {
            return [null, mb_strtolower($from)];
        }

        return [null, null];
    }

    /** Message-ID de l'en-tête d'un .eml (premier bloc, lignes repliées comprises). */
    public static function messageIdHeader(string $raw): ?string
    {
        $headers = preg_split("/\r?\n\r?\n/", EmlParser::normalize($raw), 2)[0] ?? '';
        $headers = preg_replace("/\n[ \t]+/", ' ', $headers);

        return preg_match('/^Message-ID:\s*(.+)$/im', $headers, $m) ? InboundLeadService::messageId($m[1]) : null;
    }

    /**
     * Pièces jointes réelles : les images insérées dans le corps (logos de signature,
     * repérées par leur Content-ID) sont laissées de côté.
     *
     * @param  array<int, array{name: string, mime: string, size: int, content_id: ?string, data: ?string}>  $parts
     * @return array<int, array{name: string, mime: string, data: string}>
     */
    private static function attachments(array $parts): array
    {
        $kept = [];
        foreach ($parts as $part) {
            if (($part['data'] ?? null) === null) {
                continue;
            }
            if (($part['content_id'] ?? null) !== null && str_starts_with((string) $part['mime'], 'image/')) {
                continue;
            }
            $kept[] = ['name' => $part['name'], 'mime' => $part['mime'], 'data' => $part['data']];
        }

        return $kept;
    }

    private static function textOf(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        $text = preg_replace('#<(br|/p|/div|/tr)[^>]*>#i', "\n", $html);
        $text = html_entity_decode(strip_tags((string) preg_replace('#<(style|script)[^>]*>.*?</\1>#is', '', $text)), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace("/\n{3,}/", "\n\n", $text));
    }
}

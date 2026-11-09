<?php

namespace App\Services\Files\Preview;

use App\Services\Imports\SpreadsheetReader;
use Carbon\CarbonImmutable;

/**
 * Lecture d'un e-mail au format MIME (.eml, RFC 5322 / 2045-2047 / 2231).
 *
 * Ne garde que ce qu'affiche l'aperçu de la GED : en-têtes utiles, premier
 * corps HTML et premier corps texte, pièces jointes (images insérées comprises,
 * repérées par leur Content-ID). Un message joint (message/rfc822) ressort en
 * pièce jointe .eml.
 */
class EmlParser
{
    /** Au-delà, une arborescence multipart est jugée anormale et n'est plus descendue. */
    private const MAX_DEPTH = 20;

    /**
     * @return array{subject: ?string, from: ?string, to: array<int, string>, cc: array<int, string>, date: ?string, html: ?string, text: ?string, attachments: array<int, array{name: string, mime: string, size: int, content_id: ?string, data: ?string}>}
     */
    public function parse(string $raw): array
    {
        [$headers, $body] = $this->split(self::normalize($raw));

        $message = [
            'subject' => $this->header($headers, 'subject'),
            'from' => $this->header($headers, 'from'),
            'to' => self::addresses($this->header($headers, 'to')),
            'cc' => self::addresses($this->header($headers, 'cc')),
            'date' => self::date($this->header($headers, 'date')),
            'html' => null,
            'text' => null,
            'attachments' => [],
        ];

        $this->part($headers, $body, $message, 0);

        return $message;
    }

    /**
     * Texte d'un .eml tel qu'il sort des messageries Windows : UTF-16 (avec ou
     * sans BOM) converti, BOM UTF-8 retiré, fins de ligne CRLF / CR ramenées à
     * LF, lignes vides de tête et ligne « From … » des boîtes mbox (Thunderbird)
     * retirées.
     */
    public static function normalize(string $raw): string
    {
        if (str_starts_with($raw, "\xFF\xFE")) {
            $raw = mb_convert_encoding(substr($raw, 2), 'UTF-8', 'UTF-16LE');
        } elseif (str_starts_with($raw, "\xFE\xFF")) {
            $raw = mb_convert_encoding(substr($raw, 2), 'UTF-8', 'UTF-16BE');
        } elseif (strlen($raw) >= 4 && $raw[1] === "\0" && $raw[3] === "\0" && $raw[0] !== "\0") {
            $raw = mb_convert_encoding($raw, 'UTF-8', 'UTF-16LE');
        } elseif (str_starts_with($raw, "\xEF\xBB\xBF")) {
            $raw = substr($raw, 3);
        }

        $raw = str_replace(["\r\n", "\r"], "\n", $raw);
        $raw = ltrim($raw, "\n\t ");

        if (str_starts_with($raw, 'From ')) {
            $newline = strpos($raw, "\n");
            $raw = $newline === false ? '' : substr($raw, $newline + 1);
        }

        return $raw;
    }

    /**
     * Découpe une adresse multiple en destinataires, sans couper une virgule
     * placée dans un nom entre guillemets.
     *
     * @return array<int, string>
     */
    public static function addresses(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        $parts = preg_split('/,(?=(?:[^"]*"[^"]*")*[^"]*$)/', $value) ?: [];

        return array_values(array_filter(array_map('trim', $parts), fn ($part) => $part !== ''));
    }

    public static function date(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        try {
            // « (CET) » et autres commentaires gênent le parseur ; le jour nommé
            // (« Mon, ») aussi : faux, il ferait glisser la date à ce jour-là.
            $value = preg_replace(['/\([^)]*\)/', '/^\s*[A-Za-z]{3},/'], '', $value);

            return CarbonImmutable::parse(trim($value))->toIso8601String();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, array<int, string>>  $headers
     */
    private function part(array $headers, string $body, array &$message, int $depth): void
    {
        [$type, $params] = self::parameters($this->rawHeader($headers, 'content-type') ?? 'text/plain');
        $type = strtolower($type);

        if (str_starts_with($type, 'multipart/') && isset($params['boundary']) && $depth < self::MAX_DEPTH) {
            foreach ($this->multipart($body, $params['boundary']) as $section) {
                [$partHeaders, $partBody] = $this->split($section);
                $this->part($partHeaders, $partBody, $message, $depth + 1);
            }

            return;
        }

        [$disposition, $dispositionParams] = self::parameters($this->rawHeader($headers, 'content-disposition') ?? '');
        $name = $dispositionParams['filename'] ?? $params['name'] ?? null;
        $isAttachment = strtolower($disposition) === 'attachment' || ($name !== null && ! str_starts_with($type, 'text/'));

        $data = $this->decodeBody($body, strtolower(trim($this->rawHeader($headers, 'content-transfer-encoding') ?? '')));

        if ($type === 'message/rfc822') {
            $inner = $this->split(str_replace("\r\n", "\n", $data))[0];
            $subject = $this->header($inner, 'subject');
            $name ??= ($subject !== null && $subject !== '' ? $subject : 'message') . '.eml';
            $isAttachment = true;
        }

        if (! $isAttachment && $type === 'text/html' && $message['html'] === null) {
            $message['html'] = self::toUtf8($data, $params['charset'] ?? null);

            return;
        }

        if (! $isAttachment && $type === 'text/plain' && $message['text'] === null) {
            $message['text'] = self::toUtf8($data, $params['charset'] ?? null);

            return;
        }

        $contentId = $this->rawHeader($headers, 'content-id');

        // Un texte de plus (deuxième corps, signature) sans nom ni Content-ID
        // n'est pas une pièce jointe que l'utilisateur a voulu envoyer.
        if (! $isAttachment && $contentId === null && str_starts_with($type, 'text/')) {
            return;
        }

        $message['attachments'][] = [
            'name' => $name ?? 'piece-jointe-' . (count($message['attachments']) + 1),
            'mime' => $type,
            'size' => strlen($data),
            'content_id' => $contentId !== null ? trim($contentId, " \t<>") : null,
            'data' => $data,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function multipart(string $body, string $boundary): array
    {
        $delimiter = '--' . $boundary;
        $sections = [];

        // Le préambule (avant le premier séparateur) et l'épilogue (après le
        // séparateur final) ne font pas partie du message.
        $chunks = explode("\n" . $delimiter, "\n" . $body);
        array_shift($chunks);

        foreach ($chunks as $chunk) {
            if (str_starts_with($chunk, '--')) {
                break;
            }

            // Reste de la ligne du séparateur (espaces tolérés), puis le contenu.
            $newline = strpos($chunk, "\n");
            $sections[] = $newline === false ? '' : substr($chunk, $newline + 1);
        }

        return $sections;
    }

    /**
     * @return array{0: array<string, array<int, string>>, 1: string}
     */
    private function split(string $raw): array
    {
        // Ligne vide de séparation, parfois faite d'espaces ; une partie sans
        // en-tête commence directement par elle.
        if (str_starts_with($raw, "\n")) {
            $head = '';
            $body = substr($raw, 1);
        } elseif (preg_match('/\n[ \t]*\n/', $raw, $m, PREG_OFFSET_CAPTURE)) {
            $head = substr($raw, 0, $m[0][1]);
            $body = substr($raw, $m[0][1] + strlen($m[0][0]));
        } else {
            $head = $raw;
            $body = '';
        }

        $headers = [];
        $current = null;
        foreach (explode("\n", $head) as $line) {
            if ($line !== '' && ($line[0] === ' ' || $line[0] === "\t") && $current !== null) {
                $last = array_key_last($headers[$current]);
                $headers[$current][$last] .= ' ' . ltrim($line);

                continue;
            }

            $colon = strpos($line, ':');
            if ($colon === false) {
                continue;
            }

            $current = strtolower(trim(substr($line, 0, $colon)));
            $headers[$current][] = trim(substr($line, $colon + 1));
        }

        return [$headers, $body];
    }

    private function rawHeader(array $headers, string $name): ?string
    {
        return $headers[$name][0] ?? null;
    }

    /**
     * En-tête décodé (« =?UTF-8?B?…?= »), en UTF-8.
     */
    private function header(array $headers, string $name): ?string
    {
        $value = $this->rawHeader($headers, $name);

        return $value === null ? null : self::decodeHeader($value);
    }

    public static function decodeHeader(string $value): string
    {
        if (str_contains($value, '=?')) {
            // Deux mots encodés séparés par des blancs se recollent (RFC 2047 §6.2).
            $value = preg_replace('/(\?=)\s+(=\?)/', '$1$2', $value);
            $decoded = @iconv_mime_decode($value, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');

            if ($decoded !== false) {
                return $decoded;
            }
        }

        return SpreadsheetReader::normalizeEncoding($value) ?? '';
    }

    /**
     * « text/plain; charset="utf-8"; name*=UTF-8''devis%20n%C2%B01.pdf »
     *
     * @return array{0: string, 1: array<string, string>}
     */
    public static function parameters(string $value): array
    {
        $parts = preg_split('/;(?=(?:[^"]*"[^"]*")*[^"]*$)/', $value) ?: [''];
        $main = trim(array_shift($parts));

        $params = [];
        $continued = [];
        foreach ($parts as $part) {
            $equals = strpos($part, '=');
            if ($equals === false) {
                continue;
            }

            $key = strtolower(trim(substr($part, 0, $equals)));
            $raw = trim(substr($part, $equals + 1));
            $raw = strlen($raw) >= 2 && $raw[0] === '"' && str_ends_with($raw, '"') ? preg_replace('/\\\\(.)/s', '$1', substr($raw, 1, -1)) : $raw;

            // RFC 2231 : nom*0*=…, nom*1*=… ; le * final signale un texte encodé.
            if (preg_match('/^([^*]+)\*(\d+)?(\*)?$/', $key, $m)) {
                $continued[$m[1]][(int) ($m[2] ?? 0)] = [$raw, ($m[3] ?? '') === '*' || ! isset($m[2])];

                continue;
            }

            $params[$key] = self::decodeHeader($raw);
        }

        foreach ($continued as $key => $pieces) {
            ksort($pieces);
            $charset = null;
            $text = '';
            foreach ($pieces as $index => [$piece, $encoded]) {
                if ($encoded && $index === array_key_first($pieces) && preg_match("/^([^']*)'[^']*'(.*)$/s", $piece, $m)) {
                    $charset = $m[1] !== '' ? $m[1] : null;
                    $piece = $m[2];
                }
                $text .= $encoded ? rawurldecode($piece) : $piece;
            }
            $params[$key] = self::toUtf8($text, $charset);
        }

        return [$main, $params];
    }

    private function decodeBody(string $body, string $encoding): string
    {
        return match ($encoding) {
            'base64' => (string) base64_decode(preg_replace('/[^A-Za-z0-9+\/=]/', '', $body)),
            'quoted-printable' => quoted_printable_decode($body),
            default => $body,
        };
    }

    public static function toUtf8(string $text, ?string $charset): string
    {
        $charset = $charset !== null ? strtolower(trim($charset)) : null;

        if ($charset !== null && $charset !== '' && ! in_array($charset, ['utf-8', 'utf8', 'us-ascii'], true)) {
            try {
                $converted = @mb_convert_encoding($text, 'UTF-8', $charset);

                if (is_string($converted)) {
                    return $converted;
                }
            } catch (\Throwable) {
                // Jeu de caractères inconnu de mbstring : on retombe sur la détection.
            }
        }

        return SpreadsheetReader::normalizeEncoding($text) ?? '';
    }
}

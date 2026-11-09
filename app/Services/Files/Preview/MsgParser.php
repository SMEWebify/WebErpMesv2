<?php

namespace App\Services\Files\Preview;

use Carbon\CarbonImmutable;

/**
 * Lecture d'un e-mail Outlook (.msg, [MS-OXMSG]) : un fichier composé dont
 * chaque propriété MAPI est un flux « __substg1.0_IIIITTTT » (IIII = n° de
 * propriété, TTTT = type), les valeurs de taille fixe (dates, entiers) étant
 * regroupées dans « __properties_version1.0 ».
 *
 * Même forme de sortie que EmlParser. Un message joint (pièce jointe de type
 * « message incorporé ») est listé sans contenu téléchargeable.
 */
class MsgParser
{
    private const TYPE_UNICODE = '001F';
    private const TYPE_STRING8 = '001E';
    private const TYPE_BINARY = '0102';

    private const PT_LONG = 0x0003;
    private const PT_SYSTIME = 0x0040;

    // Propriétés lues (PidTag…).
    private const SUBJECT = '0037';
    private const CLIENT_SUBMIT_TIME = 0x0039;
    private const MESSAGE_DELIVERY_TIME = 0x0E06;
    private const CREATION_TIME = 0x3007;
    private const SENDER_NAME = '0C1A';
    private const SENDER_EMAIL = '0C1F';
    private const SENDER_SMTP = '5D01';
    private const SENT_REPRESENTING_NAME = '0042';
    private const SENT_REPRESENTING_SMTP = '5D02';
    private const DISPLAY_TO = '0E04';
    private const DISPLAY_CC = '0E03';
    private const BODY = '1000';
    private const BODY_HTML = '1013';
    private const INTERNET_CPID = 0x3FDE;
    private const MESSAGE_CODEPAGE = 0x3FFD;

    private const RECIPIENT_TYPE = 0x0C15;
    private const DISPLAY_NAME = '3001';
    private const EMAIL_ADDRESS = '3003';
    private const SMTP_ADDRESS = '39FE';

    private const ATTACH_DATA = '3701';
    private const ATTACH_FILENAME = '3704';
    private const ATTACH_LONG_FILENAME = '3707';
    private const ATTACH_MIME = '370E';
    private const ATTACH_CONTENT_ID = '3712';

    private CompoundFile $file;

    private string $codepage = 'Windows-1252';

    /**
     * @return array{subject: ?string, from: ?string, to: array<int, string>, cc: array<int, string>, date: ?string, html: ?string, text: ?string, attachments: array<int, array{name: string, mime: string, size: int, content_id: ?string, data: ?string}>}
     */
    public function parse(string $raw): array
    {
        $this->file = new CompoundFile($raw);
        $root = $this->file->children(0);

        // Message de premier niveau : en-tête de 32 octets avant les propriétés.
        $fixed = $this->fixedProperties($root, 32);
        $this->codepage = self::charset($fixed[self::INTERNET_CPID] ?? $fixed[self::MESSAGE_CODEPAGE] ?? null);

        [$to, $cc] = $this->recipients($root);

        $html = $this->string($root, self::BODY_HTML);
        if ($html === null && ($binary = $this->binary($root, self::BODY_HTML)) !== null) {
            $html = EmlParser::toUtf8($binary, $this->codepage);
        }

        return [
            'subject' => $this->string($root, self::SUBJECT),
            'from' => $this->sender($root),
            'to' => $to !== [] ? $to : EmlParser::addresses(str_replace(';', ',', (string) $this->string($root, self::DISPLAY_TO))),
            'cc' => $cc !== [] ? $cc : EmlParser::addresses(str_replace(';', ',', (string) $this->string($root, self::DISPLAY_CC))),
            'date' => self::filetime($fixed[self::CLIENT_SUBMIT_TIME] ?? $fixed[self::MESSAGE_DELIVERY_TIME] ?? $fixed[self::CREATION_TIME] ?? null),
            'html' => $html,
            'text' => $this->string($root, self::BODY),
            'attachments' => $this->attachments($root),
        ];
    }

    private function sender(array $root): ?string
    {
        $name = $this->string($root, self::SENDER_NAME) ?? $this->string($root, self::SENT_REPRESENTING_NAME);
        $email = $this->string($root, self::SENDER_SMTP) ?? $this->string($root, self::SENT_REPRESENTING_SMTP);

        // Sans adresse SMTP, l'adresse Exchange (« /O=… ») ne parle à personne.
        if ($email === null && str_contains((string) ($sender = $this->string($root, self::SENDER_EMAIL)), '@')) {
            $email = $sender;
        }

        return self::mailbox($name, $email);
    }

    /**
     * @return array{0: array<int, string>, 1: array<int, string>}
     */
    private function recipients(array $root): array
    {
        $to = [];
        $cc = [];

        foreach ($root as $name => $id) {
            if (! str_starts_with($name, '__recip_version1.0_') || ! $this->file->isStorage($id)) {
                continue;
            }

            $recipient = $this->file->children($id);
            $fixed = $this->fixedProperties($recipient, 8);
            $email = $this->string($recipient, self::SMTP_ADDRESS);

            if ($email === null && str_contains((string) ($address = $this->string($recipient, self::EMAIL_ADDRESS)), '@')) {
                $email = $address;
            }

            $mailbox = self::mailbox($this->string($recipient, self::DISPLAY_NAME), $email);

            if ($mailbox === null) {
                continue;
            }

            // 1 = À, 2 = Cc, 3 = Cci (jamais affiché : absent du message reçu).
            match ($fixed[self::RECIPIENT_TYPE] ?? 1) {
                2 => $cc[] = $mailbox,
                3 => null,
                default => $to[] = $mailbox,
            };
        }

        return [$to, $cc];
    }

    private function attachments(array $root): array
    {
        $attachments = [];
        $storages = array_filter($root, fn ($id, $name) => str_starts_with($name, '__attach_version1.0_') && $this->file->isStorage($id), ARRAY_FILTER_USE_BOTH);
        ksort($storages);

        foreach ($storages as $id) {
            $attachment = $this->file->children($id);
            $streamId = $attachment['__substg1.0_' . self::ATTACH_DATA . self::TYPE_BINARY] ?? null;
            $data = $streamId !== null ? $this->file->stream($streamId) : null;

            $name = $this->string($attachment, self::ATTACH_LONG_FILENAME)
                ?? $this->string($attachment, self::ATTACH_FILENAME)
                ?? $this->string($attachment, self::DISPLAY_NAME);

            // Message incorporé : un sous-stockage « __substg1.0_3701000D ».
            if ($data === null && isset($attachment['__substg1.0_' . self::ATTACH_DATA . '000D'])) {
                $name = ($name ?? 'message') . (str_ends_with(strtolower((string) $name), '.msg') ? '' : '.msg');
            }

            $attachments[] = [
                'name' => $name ?? 'piece-jointe-' . (count($attachments) + 1),
                'mime' => strtolower($this->string($attachment, self::ATTACH_MIME) ?? 'application/octet-stream'),
                'size' => $data !== null ? strlen($data) : 0,
                'content_id' => ($cid = $this->string($attachment, self::ATTACH_CONTENT_ID)) !== null ? trim($cid, " \t<>") : null,
                'data' => $data,
            ];
        }

        return $attachments;
    }

    /**
     * Propriété texte, Unicode d'abord, sinon 8 bits dans la page de code du message.
     */
    private function string(array $storage, string $property): ?string
    {
        if (isset($storage['__substg1.0_' . $property . self::TYPE_UNICODE])) {
            $value = $this->file->stream($storage['__substg1.0_' . $property . self::TYPE_UNICODE]);
            $value = mb_convert_encoding($value, 'UTF-8', 'UTF-16LE');
        } elseif (isset($storage['__substg1.0_' . $property . self::TYPE_STRING8])) {
            $value = EmlParser::toUtf8($this->file->stream($storage['__substg1.0_' . $property . self::TYPE_STRING8]), $this->codepage);
        } else {
            return null;
        }

        $value = rtrim($value, "\0");

        return trim($value) === '' ? null : $value;
    }

    private function binary(array $storage, string $property): ?string
    {
        $id = $storage['__substg1.0_' . $property . self::TYPE_BINARY] ?? null;

        return $id === null ? null : $this->file->stream($id);
    }

    /**
     * Valeurs de taille fixe (entiers, dates) du flux de propriétés.
     *
     * @return array<int, int> n° de propriété => valeur (FILETIME brut pour une date)
     */
    private function fixedProperties(array $storage, int $headerSize): array
    {
        $id = $storage['__properties_version1.0'] ?? null;
        if ($id === null) {
            return [];
        }

        $stream = $this->file->stream($id);
        $values = [];

        for ($offset = $headerSize; $offset + 16 <= strlen($stream); $offset += 16) {
            ['type' => $type, 'id' => $property] = unpack('vtype/vid', $stream, $offset);

            if ($type === self::PT_LONG) {
                $values[$property] = unpack('V', $stream, $offset + 8)[1];
            } elseif ($type === self::PT_SYSTIME) {
                $values[$property] = unpack('P', $stream, $offset + 8)[1];
            }
        }

        return $values;
    }

    private static function mailbox(?string $name, ?string $email): ?string
    {
        $name = $name !== null ? trim($name) : null;
        $email = $email !== null ? trim($email) : null;

        if ($email === null || $email === '') {
            return $name ?: null;
        }

        return $name !== null && $name !== '' && strcasecmp($name, $email) !== 0 ? "{$name} <{$email}>" : $email;
    }

    /**
     * FILETIME : centaines de nanosecondes depuis le 1er janvier 1601 (UTC).
     */
    private static function filetime(?int $value): ?string
    {
        if ($value === null || $value <= 0) {
            return null;
        }

        $seconds = intdiv($value, 10_000_000) - 11_644_473_600;

        return CarbonImmutable::createFromTimestampUTC($seconds)->toIso8601String();
    }

    /**
     * Page de code Windows → nom compris par mbstring.
     */
    private static function charset(?int $codepage): string
    {
        return match ($codepage) {
            65001 => 'UTF-8',
            20127 => 'ASCII',
            28591 => 'ISO-8859-1',
            28605 => 'ISO-8859-15',
            1250, 1251, 1252, 1253, 1254, 1255, 1256, 1257, 1258 => 'Windows-' . $codepage,
            932 => 'SJIS-win',
            936 => 'CP936',
            949 => 'UHC',
            950 => 'CP950',
            default => 'Windows-1252',
        };
    }
}

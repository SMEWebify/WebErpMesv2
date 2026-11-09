<?php

namespace App\Services\Files\Preview;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Aperçu d'un e-mail rangé dans la GED (.eml, .msg).
 *
 * Le HTML d'un mail vient de l'extérieur : il est nettoyé ici (aucun script,
 * aucun attribut d'événement, aucune ressource distante — une image de suivi
 * ne doit pas prévenir l'expéditeur qu'on a ouvert le mail), puis affiché par
 * le navigateur dans une iframe isolée (sandbox, CSP). Les images insérées
 * (cid:) sont reprises en data: URI.
 */
class MailPreview
{
    private static ?HTMLPurifier $purifier = null;

    /**
     * Message lu, pièces jointes avec leur contenu.
     */
    public function parse(string $raw, string $extension): array
    {
        return strtolower($extension) === 'msg'
            ? (new MsgParser())->parse($raw)
            : (new EmlParser())->parse($raw);
    }

    /**
     * Forme envoyée au navigateur : jamais le contenu des pièces jointes, qui
     * passent par une route de téléchargement (index = position dans parse()).
     */
    public function read(string $raw, string $extension): array
    {
        $message = $this->parse($raw, $extension);
        $embedded = [];
        $html = $message['html'] !== null ? $this->embedInlineImages($message['html'], $message['attachments'], $embedded) : null;

        $attachments = [];
        foreach ($message['attachments'] as $index => $attachment) {
            // Une image déjà affichée dans le corps n'est pas une pièce jointe pour le lecteur.
            if (isset($embedded[$index])) {
                continue;
            }

            $attachments[] = [
                'index' => $index,
                'name' => $attachment['name'],
                'mime' => $attachment['mime'],
                'size' => $attachment['size'],
                'downloadable' => $attachment['data'] !== null,
            ];
        }

        return [
            'type' => 'mail',
            'subject' => $message['subject'],
            'from' => $message['from'],
            'to' => $message['to'],
            'cc' => $message['cc'],
            'date' => $message['date'],
            'html' => $html !== null ? self::clean($html) : null,
            'text' => $message['text'],
            'attachments' => $attachments,
        ];
    }

    public static function clean(string $html): string
    {
        return self::purifier()->purify($html);
    }

    /**
     * @param  array<int, array{content_id: ?string, mime: string, data: ?string}>  $attachments
     * @param  array<int, true>  $embedded  index des pièces reprises dans le corps
     */
    private function embedInlineImages(string $html, array $attachments, array &$embedded): string
    {
        $limit = (int) config('files.preview.max_inline_image_bytes', 2 * 1024 * 1024);

        return preg_replace_callback('/cid:([^"\'\s>)]+)/i', function (array $match) use ($attachments, $limit, &$embedded) {
            $cid = trim(rawurldecode($match[1]), '<>');

            foreach ($attachments as $index => $attachment) {
                if ($attachment['content_id'] === null || strcasecmp($attachment['content_id'], $cid) !== 0) {
                    continue;
                }

                if ($attachment['data'] === null || ! str_starts_with($attachment['mime'], 'image/') || strlen($attachment['data']) > $limit) {
                    break;
                }

                $embedded[$index] = true;

                return 'data:' . $attachment['mime'] . ';base64,' . base64_encode($attachment['data']);
            }

            return $match[0];
        }, $html) ?? $html;
    }

    private static function purifier(): HTMLPurifier
    {
        if (self::$purifier === null) {
            $config = HTMLPurifier_Config::createDefault();
            $config->set('Cache.DefinitionImpl', null);
            $config->set('URI.AllowedSchemes', [
                'http' => true,
                'https' => true,
                'mailto' => true,
                'tel' => true,
                'data' => true,
            ]);
            // Liens gardés (ouverts dans un onglet), images distantes retirées.
            $config->set('URI.DisableExternalResources', true);
            $config->set('HTML.TargetBlank', true);
            $config->set('Attr.AllowedFrameTargets', ['_blank']);

            self::$purifier = new HTMLPurifier($config);
        }

        return self::$purifier;
    }
}

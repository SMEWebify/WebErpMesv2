<?php

namespace App\Http\Controllers\Files;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\User;
use App\Services\Files\FileStorageService;
use App\Services\Files\OfficeEdit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Le strict nécessaire de WebDAV (RFC 4918, classes 1 et 2) pour qu'Excel et
 * Word ouvrent un fichier de la GED, le verrouillent et l'enregistrent :
 * OPTIONS, PROPFIND, GET / HEAD, LOCK / UNLOCK, PUT. Un dossier virtuel
 * /dav/{jeton}/ ne contient que ce fichier.
 *
 * Hors du groupe « web » (ni session, ni CSRF : Office n'en a pas) ; l'accès
 * repose sur le jeton de OfficeEdit, et les droits de l'utilisateur sont
 * relus à chaque requête.
 */
class OfficeDavController extends Controller
{
    private const MIME_TYPES = [
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xls' => 'application/vnd.ms-excel',
        'csv' => 'text/csv',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'doc' => 'application/msword',
    ];

    private const ALLOW = 'OPTIONS, GET, HEAD, PUT, PROPFIND, PROPPATCH, LOCK, UNLOCK';

    public function __construct(
        private readonly OfficeEdit $edits,
        private readonly FileStorageService $storage,
    ) {
    }

    public function handle(Request $request, string $token, ?string $name = null): Response
    {
        $method = $request->getMethod();

        if ($method === 'OPTIONS') {
            return $this->options();
        }

        $session = $this->edits->session($token);
        $user = $session !== null ? $this->edits->user($session) : null;
        $file = $session !== null ? $this->edits->file($session) : null;

        if ($session === null || $user === null || $file === null) {
            return $this->status(404);
        }

        if (! $this->edits->canRead($user, $file)) {
            return $this->status(403);
        }

        $isFolder = $name === null || $name === '';

        return match ($method) {
            'PROPFIND' => $this->propfind($request, $token, $session, $file, $isFolder),
            'PROPPATCH' => $this->proppatch($request),
            'GET', 'HEAD' => $isFolder ? $this->status(405) : $this->get($file, $method === 'HEAD'),
            'LOCK' => $this->lock($request, $token, $user, $file),
            'UNLOCK' => $this->unlock($request, $token),
            'PUT' => $isFolder ? $this->status(405) : $this->put($request, $token, $session, $user, $file),
            default => $this->status(405),
        };
    }

    private function options(): Response
    {
        return new Response('', 200, [
            'DAV' => '1, 2',
            'MS-Author-Via' => 'DAV',
            'Allow' => self::ALLOW,
            'Content-Length' => '0',
        ]);
    }

    private function get(File $file, bool $headOnly): Response
    {
        $located = $this->storage->locate($file);

        if ($located === null) {
            return $this->status(404);
        }

        [$disk, $path] = $located;
        $contents = $headOnly ? '' : (string) Storage::disk($disk)->get($path);

        return new Response($contents, 200, [
            'Content-Type' => $this->mimeType($file),
            'Content-Length' => (string) ($headOnly ? Storage::disk($disk)->size($path) : strlen($contents)),
            'Last-Modified' => $this->lastModified($file),
            'ETag' => $this->etag($file),
            // Jamais affiché par notre origine si quelqu'un ouvre l'adresse dans un navigateur.
            'Content-Disposition' => 'attachment',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }

    private function put(Request $request, string $token, array $session, User $user, File $file): Response
    {
        if (! $this->edits->canWrite($user, $file)) {
            return $this->status(403);
        }

        $lock = $this->edits->lock($token);
        if ($lock !== null && ! str_contains((string) $request->header('If'), $lock['token'])) {
            return $this->status(423);
        }

        $contents = $request->getContent();

        if (strlen($contents) > (int) config('files.max_size', 51200) * 1024) {
            return $this->status(413);
        }

        try {
            $this->edits->save($token, $session, $file, $user, $contents);
        } catch (ConflictHttpException) {
            // Remplacé entre-temps par quelqu'un d'autre : Office propose « Enregistrer sous ».
            return $this->status(409);
        }

        return $this->status(204);
    }

    private function lock(Request $request, string $token, User $user, File $file): Response
    {
        if (! $this->edits->canWrite($user, $file)) {
            return $this->status(403);
        }

        $current = $this->edits->lock($token);
        $seconds = $this->timeout((string) $request->header('Timeout'));
        $body = trim($request->getContent());

        if ($current !== null) {
            // Renouvellement : corps vide et jeton du verrou dans If.
            if ($body !== '' || ! str_contains((string) $request->header('If'), $current['token'])) {
                return $this->status(423);
            }

            $lock = $this->edits->putLock($token, $current['token'], $seconds);
        } else {
            $lock = $this->edits->putLock($token, null, $seconds);
        }

        $owner = '';
        if ($body !== '' && preg_match('~<(?:\w+:)?owner[^>]*>(.*?)</(?:\w+:)?owner>~s', $body, $m)) {
            $owner = '<D:owner>' . strip_tags($m[1]) . '</D:owner>';
        }

        $xml = '<?xml version="1.0" encoding="utf-8"?>'
            . '<D:prop xmlns:D="DAV:"><D:lockdiscovery>' . $this->activeLock($lock, $owner) . '</D:lockdiscovery></D:prop>';

        return new Response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'Lock-Token' => '<' . $lock['token'] . '>',
        ]);
    }

    private function unlock(Request $request, string $token): Response
    {
        $current = $this->edits->lock($token);

        if ($current !== null && trim((string) $request->header('Lock-Token'), " <>") === $current['token']) {
            $this->edits->releaseLock($token);
        }

        return $this->status(204);
    }

    private function propfind(Request $request, string $token, array $session, File $file, bool $isFolder): Response
    {
        $folderHref = route('office.dav.file', ['token' => $token], false) . '/';
        $fileHref = route('office.dav.file', ['token' => $token, 'name' => $session['name']], false);
        $lock = $this->edits->lock($token);

        $responses = [];
        if ($isFolder) {
            $responses[] = $this->folderResponse($folderHref);

            if ($request->header('Depth', '1') !== '0') {
                $responses[] = $this->fileResponse($fileHref, $session['name'], $file, $lock);
            }
        } else {
            $responses[] = $this->fileResponse($fileHref, $session['name'], $file, $lock);
        }

        return $this->multistatus(implode('', $responses));
    }

    /**
     * Office écrit parfois des propriétés après un enregistrement : acceptées
     * sans être gardées.
     */
    private function proppatch(Request $request): Response
    {
        $href = htmlspecialchars($request->getRequestUri(), ENT_XML1);

        return $this->multistatus("<D:response><D:href>{$href}</D:href><D:propstat><D:prop/><D:status>HTTP/1.1 200 OK</D:status></D:propstat></D:response>");
    }

    private function folderResponse(string $href): string
    {
        return '<D:response><D:href>' . htmlspecialchars($href, ENT_XML1) . '</D:href><D:propstat><D:prop>'
            . '<D:resourcetype><D:collection/></D:resourcetype>'
            . '<D:displayname></D:displayname>'
            . '</D:prop><D:status>HTTP/1.1 200 OK</D:status></D:propstat></D:response>';
    }

    private function fileResponse(string $href, string $name, File $file, ?array $lock): string
    {
        $created = ($file->created_at ?? now())->copy()->utc()->format('Y-m-d\TH:i:s\Z');

        return '<D:response><D:href>' . htmlspecialchars($href, ENT_XML1) . '</D:href><D:propstat><D:prop>'
            . '<D:resourcetype/>'
            . '<D:displayname>' . htmlspecialchars($name, ENT_XML1) . '</D:displayname>'
            . '<D:getcontentlength>' . (int) $file->size . '</D:getcontentlength>'
            . '<D:getcontenttype>' . $this->mimeType($file) . '</D:getcontenttype>'
            . '<D:getlastmodified>' . $this->lastModified($file) . '</D:getlastmodified>'
            . '<D:creationdate>' . $created . '</D:creationdate>'
            . '<D:getetag>' . htmlspecialchars($this->etag($file), ENT_XML1) . '</D:getetag>'
            . '<D:supportedlock><D:lockentry><D:lockscope><D:exclusive/></D:lockscope><D:locktype><D:write/></D:locktype></D:lockentry></D:supportedlock>'
            . '<D:lockdiscovery>' . ($lock !== null ? $this->activeLock($lock, '') : '') . '</D:lockdiscovery>'
            . '</D:prop><D:status>HTTP/1.1 200 OK</D:status></D:propstat></D:response>';
    }

    private function activeLock(array $lock, string $owner): string
    {
        return '<D:activelock><D:locktype><D:write/></D:locktype><D:lockscope><D:exclusive/></D:lockscope>'
            . '<D:depth>0</D:depth>' . $owner
            . '<D:timeout>Second-' . $lock['seconds'] . '</D:timeout>'
            . '<D:locktoken><D:href>' . htmlspecialchars($lock['token'], ENT_XML1) . '</D:href></D:locktoken>'
            . '</D:activelock>';
    }

    private function multistatus(string $responses): Response
    {
        return new Response(
            '<?xml version="1.0" encoding="utf-8"?><D:multistatus xmlns:D="DAV:">' . $responses . '</D:multistatus>',
            207,
            ['Content-Type' => 'application/xml; charset=utf-8'],
        );
    }

    /**
     * « Second-3600 », « Infinite, Second-4100 » : la première durée chiffrée.
     */
    private function timeout(string $header): int
    {
        return preg_match('/Second-(\d+)/i', $header, $m) ? (int) $m[1] : 3600;
    }

    private function mimeType(File $file): string
    {
        return self::MIME_TYPES[strtolower((string) $file->extension)] ?? 'application/octet-stream';
    }

    private function lastModified(File $file): string
    {
        return ($file->updated_at ?? $file->created_at ?? now())->copy()->utc()->format('D, d M Y H:i:s') . ' GMT';
    }

    private function etag(File $file): string
    {
        return '"' . sha1($file->id . '-' . $file->size . '-' . $file->updated_at) . '"';
    }

    private function status(int $code): Response
    {
        return new Response('', $code, $code === 405 ? ['Allow' => self::ALLOW] : []);
    }
}

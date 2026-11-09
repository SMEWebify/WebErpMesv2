<?php

namespace App\Services\Files\Preview;

use App\Models\File;
use App\Services\Files\FileKindResolver;
use App\Services\Files\FileStorageService;
use Illuminate\Support\Facades\Storage;

/**
 * Aperçu, calculé par le serveur, des fichiers que le navigateur ne sait pas
 * afficher seul : tableurs et e-mails. Lecture seule, rien n'est écrit.
 */
class FilePreview
{
    public function __construct(private readonly FileStorageService $storage)
    {
    }

    public static function supports(?string $kind): bool
    {
        return in_array($kind, [FileKindResolver::KIND_SHEET, FileKindResolver::KIND_MAIL], true);
    }

    /**
     * @throws PreviewUnavailable
     */
    public function read(File $file): array
    {
        $kind = $file->kind ?? FileKindResolver::KIND_OTHER;

        if (! self::supports($kind)) {
            throw new PreviewUnavailable('unsupported');
        }

        $raw = $this->bytes($file);
        $extension = $this->extension($file);

        try {
            if ($kind === FileKindResolver::KIND_MAIL) {
                return (new MailPreview())->read($raw, $extension);
            }

            return $this->withTemporaryFile($raw, fn (string $path) => SpreadsheetPreview::fromConfig()
                ->read($path, $extension, $file->original_file_name ?: $file->name));
        } catch (PreviewUnavailable $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            throw new PreviewUnavailable('unreadable');
        }
    }

    /**
     * Pièce jointe d'un e-mail, par sa position dans le message.
     *
     * @return array{name: string, data: string}
     *
     * @throws PreviewUnavailable
     */
    public function attachment(File $file, int $index): array
    {
        if (($file->kind ?? null) !== FileKindResolver::KIND_MAIL) {
            throw new PreviewUnavailable('unsupported');
        }

        try {
            $attachment = (new MailPreview())->parse($this->bytes($file), $this->extension($file))['attachments'][$index] ?? null;
        } catch (PreviewUnavailable $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            throw new PreviewUnavailable('unreadable');
        }

        if ($attachment === null || $attachment['data'] === null) {
            throw new PreviewUnavailable('missing');
        }

        return ['name' => $attachment['name'], 'data' => $attachment['data']];
    }

    private function bytes(File $file): string
    {
        $located = $this->storage->locate($file);

        if ($located === null) {
            throw new PreviewUnavailable('missing');
        }

        [$disk, $path] = $located;

        if (Storage::disk($disk)->size($path) > (int) config('files.preview.max_bytes', 15 * 1024 * 1024)) {
            throw new PreviewUnavailable('too_large');
        }

        return (string) Storage::disk($disk)->get($path);
    }

    private function extension(File $file): string
    {
        return strtolower($file->extension ?: (string) FileKindResolver::extensionOf($file->original_file_name ?: $file->name));
    }

    private function withTemporaryFile(string $contents, callable $callback): mixed
    {
        $path = tempnam(sys_get_temp_dir(), 'wem-preview-');
        file_put_contents($path, $contents);

        try {
            return $callback($path);
        } finally {
            @unlink($path);
        }
    }
}

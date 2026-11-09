<?php

namespace App\Services\Files;

use App\Models\File;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * Modification d'un fichier de la GED dans l'Office du poste (Excel, Word).
 *
 * open() crée une session : un jeton aléatoire (porté par l'adresse WebDAV,
 * Office ne partageant pas la session du navigateur) lié à l'utilisateur et au
 * fichier, gardé dans le cache — aucune table. Office lit le fichier, le
 * verrouille, puis chaque enregistrement devient une nouvelle version
 * (FileVersioning::replace) ; la session suit alors la dernière version.
 * Les droits sont relus à chaque requête : un droit retiré ferme la porte.
 */
class OfficeEdit
{
    private const SESSION = 'office-edit:';
    private const LOCK = 'office-edit-lock:';

    /** Un verrou Office se renouvelle ; au-delà, il tombe de lui-même. */
    private const MAX_LOCK_SECONDS = 3600;

    public function __construct(private readonly FileVersioning $versioning)
    {
    }

    public static function enabled(): bool
    {
        return (bool) config('files.office_edit.enabled', false);
    }

    /**
     * Schéma Office (ms-excel, ms-word) du fichier, null s'il ne s'édite pas ainsi.
     */
    public static function schemeFor(File $file): ?string
    {
        if (! self::enabled() || $file->isSuperseded()) {
            return null;
        }

        return config('files.office_edit.schemes')[strtolower((string) $file->extension)] ?? null;
    }

    /**
     * Ouvre une session d'édition et renvoie le lien qui lance Office.
     */
    public function open(File $file, User $user): string
    {
        $scheme = self::schemeFor($file);

        abort_if($scheme === null, 404);

        $token = Str::random(48);

        $this->store($token, [
            'file_id' => $file->id,
            'user_id' => $user->id,
            'name' => self::davName($file),
            // Les requêtes d'Office n'ont pas de préfixe de langue : celle du clic sert.
            'locale' => app()->getLocale(),
        ]);

        // « ofe » = ouvrir pour modification ([MS-OFFICEPROTOCOL]).
        return $scheme . ':ofe|u|' . route('office.dav.file', ['token' => $token, 'name' => self::davName($file)]);
    }

    /**
     * Session d'un jeton, prolongée à chaque usage ; null si inconnu ou expiré.
     *
     * @return array{file_id: int, user_id: int, name: string, locale?: string}|null
     */
    public function session(string $token): ?array
    {
        $session = Cache::get(self::SESSION . hash('sha256', $token));

        if (! is_array($session)) {
            return null;
        }

        $this->store($token, $session);

        return $session;
    }

    public function user(array $session): ?User
    {
        return User::find($session['user_id']);
    }

    public function file(array $session): ?File
    {
        return File::find($session['file_id']);
    }

    public function canRead(User $user, File $file): bool
    {
        return Gate::forUser($user)->allows('view', $file);
    }

    public function canWrite(User $user, File $file): bool
    {
        return Gate::forUser($user)->allows('replace', $file);
    }

    /**
     * Enregistrement depuis Office : une nouvelle version, que la session suit.
     */
    public function save(string $token, array $session, File $current, User $user, string $contents): File
    {
        $path = tempnam(sys_get_temp_dir(), 'wem-office-');
        file_put_contents($path, $contents);

        try {
            $upload = new UploadedFile(
                $path,
                $current->original_file_name ?: $current->name,
                $current->type ?: 'application/octet-stream',
                null,
                true,
            );

            // Version, journal et dépôt rattachés à l'utilisateur de la session.
            Auth::setUser($user);

            $created = $this->versioning->replace($current, $upload, [
                'comment' => __('general_content.file_office_edit_comment_trans_key', [], $session['locale'] ?? null),
                'revision_label' => $current->revision_label,
            ], $user);
        } finally {
            @unlink($path);
        }

        $session['file_id'] = $created->id;
        $this->store($token, $session);

        return $created;
    }

    /**
     * Verrou en cours : ['token' => opaquelocktoken, 'expires' => timestamp] ou null.
     */
    public function lock(string $token): ?array
    {
        $lock = Cache::get(self::LOCK . hash('sha256', $token));

        return is_array($lock) && $lock['expires'] > time() ? $lock : null;
    }

    public function putLock(string $token, ?string $lockToken, int $seconds): array
    {
        $seconds = max(1, min($seconds, self::MAX_LOCK_SECONDS));
        $lock = [
            'token' => $lockToken ?? 'opaquelocktoken:' . Str::uuid(),
            'expires' => time() + $seconds,
            'seconds' => $seconds,
        ];

        Cache::put(self::LOCK . hash('sha256', $token), $lock, $seconds);

        return $lock;
    }

    public function releaseLock(string $token): void
    {
        Cache::forget(self::LOCK . hash('sha256', $token));
    }

    /**
     * Nom du fichier dans l'adresse : celui de l'utilisateur, sans caractère
     * qu'Office ou une URL interpréteraient.
     */
    public static function davName(File $file): string
    {
        $name = $file->original_file_name ?: $file->name;
        $name = trim((string) preg_replace('~[\x00-\x1F\x7F/\\\\?#%*:|"<>]~u', '_', $name));

        return $name !== '' ? $name : 'document.' . $file->extension;
    }

    private function store(string $token, array $session): void
    {
        Cache::put(
            self::SESSION . hash('sha256', $token),
            $session,
            now()->addMinutes(max(1, (int) config('files.office_edit.ttl_minutes', 480))),
        );
    }
}

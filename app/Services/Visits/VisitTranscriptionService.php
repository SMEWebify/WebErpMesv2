<?php

namespace App\Services\Visits;

use App\Services\AI\AISettingsResolver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Transcrit le mémo vocal d'une visite via Whisper (OVHcloud AI Endpoints,
 * API compatible OpenAI /audio/transcriptions, hébergée en France).
 *
 * L'audio n'est jamais écrit sur le disque de l'application : on lit le
 * fichier temporaire de la requête, que PHP supprime à la fin de celle-ci.
 * Si la transcription échoue, c'est le navigateur qui garde l'enregistrement
 * pour un nouvel essai.
 */
class VisitTranscriptionService
{
    public function __construct(private readonly AISettingsResolver $settings)
    {
    }

    /**
     * Disponible dès qu'une clé OVH est renseignée (écran IA ou .env).
     * L'accès anonyme OVH existe mais son débit est trop faible pour un mémo
     * de plusieurs minutes : sans clé, l'écran propose la dictée navigateur.
     */
    public function isAvailable(): bool
    {
        return (bool) config('ai.transcription.enabled', true)
            && ! empty($this->apiKey());
    }

    /**
     * @throws RuntimeException message affichable tel quel à l'utilisateur
     */
    public function transcribe(UploadedFile $audio, ?string $language = null): string
    {
        if (! $this->isAvailable()) {
            throw new RuntimeException(__('visits.transcription_unavailable'));
        }

        $stream = fopen($audio->getRealPath(), 'r');

        try {
            $response = Http::withToken($this->apiKey())
                ->acceptJson()
                ->timeout((int) config('ai.transcription.timeout', 180))
                ->attach('file', $stream, $this->filename($audio))
                ->post($this->endpoint(), array_filter([
                    'model' => config('ai.transcription.model', 'whisper-large-v3'),
                    'language' => $language,
                    'response_format' => 'json',
                ]));
        } catch (\Throwable $e) {
            Log::warning('Visit transcription unreachable', ['error' => $e->getMessage()]);
            throw new RuntimeException(__('visits.transcription_failed'));
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        if ($response->failed()) {
            Log::warning('Visit transcription failed', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 500),
            ]);
            throw new RuntimeException(__('visits.transcription_failed'));
        }

        return trim((string) $response->json('text', ''));
    }

    private function apiKey(): ?string
    {
        return $this->settings->openAiCompatible('ovh')['api_key'] ?: null;
    }

    private function endpoint(): string
    {
        $url = config('ai.transcription.url');

        if (! empty($url)) {
            return $url;
        }

        return $this->settings->openAiCompatible('ovh')['base_url'] . '/audio/transcriptions';
    }

    /**
     * Whisper déduit le format de l'extension : un blob MediaRecorder arrive
     * nommé « blob », on lui redonne une extension cohérente avec son type.
     */
    private function filename(UploadedFile $audio): string
    {
        $mime = (string) $audio->getMimeType();

        $extension = match (true) {
            str_contains($mime, 'webm') => 'webm',
            str_contains($mime, 'ogg') => 'ogg',
            str_contains($mime, 'mp4'), str_contains($mime, 'm4a'), str_contains($mime, 'aac') => 'm4a',
            str_contains($mime, 'mpeg'), str_contains($mime, 'mp3') => 'mp3',
            str_contains($mime, 'wav') => 'wav',
            default => $audio->getClientOriginalExtension() ?: 'webm',
        };

        return 'memo.' . $extension;
    }
}

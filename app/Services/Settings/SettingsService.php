<?php

namespace App\Services\Settings;

use App\Models\Setting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class SettingsService
{
    private const CACHE_PREFIX = 'settings.';

    /**
     * Clés dont la valeur est chiffrée en base (tokens d'intégration, secrets).
     */
    private const SECRET_KEYS = [
        'n2p_api_token',
    ];

    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever($this->cacheKey($key), function () use ($key, $default) {
            $setting = Setting::query()->where('key', $key)->first();

            if (!$setting) {
                return $default;
            }

            $raw = $setting->value;
            if ($this->isSecret($key) && is_string($raw) && $raw !== '') {
                try {
                    $raw = Crypt::decryptString($raw);
                } catch (\Throwable $e) {
                    // Valeur en clair (pré-migration) — on retourne tel quel.
                }
            }

            return $this->decodeValue($raw, $default);
        });
    }

    public function getMany(array $keys, array $defaults = []): array
    {
        $values = [];
        foreach ($keys as $key) {
            $values[$key] = $this->get($key, Arr::get($defaults, $key));
        }

        return $values;
    }

    public function set(string $key, mixed $value): void
    {
        $encoded = $this->encodeValue($value);

        if ($this->isSecret($key) && $encoded !== '') {
            $encoded = Crypt::encryptString($encoded);
        }

        Setting::query()->updateOrCreate(['key' => $key], ['value' => $encoded]);
        Cache::forget($this->cacheKey($key));
    }

    private function isSecret(string $key): bool
    {
        return in_array($key, self::SECRET_KEYS, true);
    }

    public function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }
    }

    private function cacheKey(string $key): string
    {
        return self::CACHE_PREFIX . $key;
    }

    private function encodeValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }

        return (string) $value;
    }

    private function decodeValue(string $value, mixed $default): mixed
    {
        $trimmed = trim($value);

        if ($trimmed === 'null') {
            return null;
        }

        if (is_numeric($trimmed)) {
            return $trimmed + 0;
        }

        $lower = strtolower($trimmed);
        if (in_array($lower, ['true', 'false'], true)) {
            return $lower === 'true';
        }

        $json = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            return $json;
        }

        if ($value === '') {
            return $default;
        }

        return $value;
    }
}

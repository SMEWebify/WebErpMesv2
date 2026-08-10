<?php

namespace App\Services\Cad;

use Illuminate\Http\UploadedFile;

/**
 * Shared plumbing for the format parsers: naming, number formatting and the
 * normalisation of the array they all have to return.
 */
abstract class BaseCadParser implements CadParser
{
    /** Longest value the code / label / material columns accept. */
    protected const COLUMN_LENGTH = 255;

    public function gedRole(): ?string
    {
        return null;
    }

    /**
     * Upload name without its extension, used whenever the file carries no
     * internal part number.
     */
    protected function baseName(UploadedFile $file): string
    {
        return pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
    }

    /**
     * Open the upload for a streaming read.
     *
     * A 50 MB STEP or DXF splitted in memory would be far heavier than the
     * file itself, so every parser walks the file line by line instead.
     *
     * @return resource
     */
    protected function open(UploadedFile $file)
    {
        $handle = @fopen($file->getRealPath(), 'rb');

        if ($handle === false) {
            throw new \RuntimeException('Fichier illisible');
        }

        return $handle;
    }

    /**
     * Read at most $bytes from the start of the upload.
     */
    protected function head(UploadedFile $file, int $bytes): string
    {
        $handle = $this->open($file);

        try {
            $content = fread($handle, $bytes);
        } finally {
            fclose($handle);
        }

        return $content === false ? '' : $content;
    }

    /**
     * Trim a float down to a readable string: 12.500 → "12.5", 12.000 → "12".
     */
    protected function number(float $value, int $decimals = 2): string
    {
        $formatted = number_format($value, $decimals, '.', '');

        return str_contains($formatted, '.')
            ? rtrim(rtrim($formatted, '0'), '.')
            : $formatted;
    }

    /**
     * Build one custom requirement, or null when there is nothing to show.
     *
     * The shape matters: the line detail screen, the guest order page and the
     * task manager all read custom_requirements as a list of {label, value}.
     */
    protected function requirement(string $label, mixed $value, string $unit = ''): ?array
    {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }

        if (is_float($value)) {
            $value = $this->number($value, 3);
        } elseif (is_bool($value)) {
            $value = 'oui';
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return [
            'label' => $label,
            'value' => $unit !== '' ? $value . ' ' . $unit : $value,
        ];
    }

    /**
     * Drop the empty requirements and return null when none is left.
     *
     * @param  array<int, array{label: string, value: string}|null>  $requirements
     * @return array<int, array{label: string, value: string}>|null
     */
    protected function requirements(array $requirements): ?array
    {
        $list = array_values(array_filter($requirements));

        return $list === [] ? null : $list;
    }

    /**
     * Assemble a "CODE - MATIERE 3mm - 100x50mm" label from the parts that
     * could actually be read.
     *
     * @param  array<int, string|null>  $parts
     */
    protected function label(array $parts): string
    {
        return implode(' - ', array_filter($parts, fn ($part) => $part !== null && $part !== ''));
    }

    /**
     * Fill in the missing keys and cut the strings to what the columns hold.
     *
     * @param  array<string, mixed>  $data
     */
    protected function result(array $data): array
    {
        // label is NOT NULL in database, so it falls back to the code and, when
        // even that is empty, to a placeholder rather than failing the insert.
        $code = $this->cut($data['code'] ?? null) ?? '';
        $label = $this->cut($data['label'] ?? null) ?? ($code !== '' ? $code : 'Import CAO');

        return [
            'code' => $code,
            'label' => $label,
            'material' => $this->cut($data['material'] ?? null),
            'thickness' => $data['thickness'] ?? null,
            'x_size' => $data['x_size'] ?? null,
            'y_size' => $data['y_size'] ?? null,
            'weight' => $data['weight'] ?? null,
            'picture' => $data['picture'] ?? null,
            'derived_svg' => $data['derived_svg'] ?? null,
            'extra' => $data['extra'] ?? null,
        ];
    }

    /**
     * Trim a value to the column length, mapping the empty string to null.
     */
    private function cut(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : mb_substr($value, 0, self::COLUMN_LENGTH);
    }
}

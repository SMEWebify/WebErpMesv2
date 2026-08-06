<?php

namespace App\Http\Requests\Files;

use App\Services\Files\FileableRegistry;
use App\Services\Files\FileKindResolver;
use App\Services\Files\FileRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFilesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Multipart form-data sérialise les booléens en chaînes littérales
     * ("true"/"false"), que la règle boolean de Laravel refuse. On normalise en
     * amont pour accepter aussi bien les clients internes (React) que les
     * clients externes (Nest4Quote, cURL...).
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('is_primary')) {
            $this->merge([
                'is_primary' => filter_var($this->input('is_primary'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'fileable_type' => ['required', 'string', Rule::in(FileableRegistry::aliases())],
            'fileable_id' => ['required', 'integer', 'min:1'],
            'files' => ['required', 'array', 'min:1', 'max:20'],
            // extensions: is checked against the client extension rather than the
            // reported MIME type, which browsers report as octet-stream for STL,
            // STEP and DXF.
            'files.*' => [
                'required',
                'file',
                'extensions:' . implode(',', FileKindResolver::allowedExtensions()),
                'max:' . config('files.max_size'),
            ],
            'role' => ['nullable', 'string', Rule::in(FileRole::all())],
            'is_primary' => ['nullable', 'boolean'],
            'comment' => ['nullable', 'string', 'max:65535'],
            'hashtags' => ['nullable', 'string', 'max:1024'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'files.*.extensions' => __('general_content.file_extension_not_allowed_trans_key'),
            'files.*.max' => __('general_content.file_too_large_trans_key'),
        ];
    }
}

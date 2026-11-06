<?php

namespace App\Http\Requests\Files;

use App\Services\Files\FileableRegistry;
use App\Services\Files\FileKindResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Upload of a new version replacing an existing GED file.
 */
class ReplaceFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'fileable_type' => ['required', 'string', Rule::in(FileableRegistry::aliases())],
            'fileable_id' => ['required', 'integer', 'min:1'],
            'file' => [
                'required',
                'file',
                'extensions:' . implode(',', FileKindResolver::allowedExtensions()),
                'max:' . config('files.max_size'),
            ],
            'revision_label' => ['nullable', 'string', 'max:20'],
            'comment' => ['nullable', 'string', 'max:65535'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.extensions' => __('general_content.file_extension_not_allowed_trans_key'),
            'file.max' => __('general_content.file_too_large_trans_key'),
        ];
    }
}

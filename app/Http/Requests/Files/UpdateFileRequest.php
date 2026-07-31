<?php

namespace App\Http\Requests\Files;

use App\Services\Files\FileableRegistry;
use App\Services\Files\FileRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFileRequest extends FormRequest
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
            'role' => ['nullable', 'string', Rule::in(FileRole::all())],
            'is_primary' => ['nullable', 'boolean'],
            'comment' => ['nullable', 'string', 'max:65535'],
            'hashtags' => ['nullable', 'string', 'max:1024'],
        ];
    }
}

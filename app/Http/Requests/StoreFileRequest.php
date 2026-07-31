<?php

namespace App\Http\Requests;

use App\Services\Files\FileKindResolver;
use Illuminate\Foundation\Http\FormRequest;

class StoreFileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            // Validated on the client extension rather than the reported MIME
            // type: browsers send application/octet-stream for STL, STEP and DXF.
            'file' => [
                'required',
                'file',
                'extensions:' . implode(',', FileKindResolver::allowedExtensions()),
                'max:' . config('files.max_size'),
            ],
            'comment' => 'nullable|string|max:65535',
            'hashtags' => 'nullable|string|max:1024',
        ];
    }
}

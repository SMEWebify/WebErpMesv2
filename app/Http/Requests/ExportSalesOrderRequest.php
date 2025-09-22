<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportSalesOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'status' => ['nullable', 'integer'],
            'date_format' => ['nullable', 'string', 'max:30'],
            'datetime_format' => ['nullable', 'string', 'max:30'],
            'include_lines' => ['nullable', 'boolean'],
        ];
    }

    public function options(): array
    {
        $validated = $this->validated();

        return [
            'from' => $validated['from'] ?? null,
            'to' => $validated['to'] ?? null,
            'status' => $validated['status'] ?? null,
            'date_format' => $validated['date_format'] ?? 'Y-m-d',
            'datetime_format' => $validated['datetime_format'] ?? 'Y-m-d H:i:s',
            'include_lines' => $this->boolean('include_lines'),
        ];
    }
}

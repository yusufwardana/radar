<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BmkgSignalIndexRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        foreach (['felt', 'active'] as $field) {
            if ($this->has($field)) {
                $this->merge([$field => filter_var($this->input($field), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'min_magnitude' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'max_magnitude' => ['nullable', 'numeric', 'min:0', 'max:10', 'gte:min_magnitude'],
            'priority' => ['nullable', 'in:LOW,MEDIUM,HIGH,CRITICAL'],
            'felt' => ['nullable', 'boolean'],
            'severity' => ['nullable', 'string', 'max:32'],
            'urgency' => ['nullable', 'string', 'max:32'],
            'certainty' => ['nullable', 'string', 'max:32'],
            'active' => ['nullable', 'boolean'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'bbox' => ['nullable', 'regex:/^-?\d+(?:\.\d+)?,-?\d+(?:\.\d+)?,-?\d+(?:\.\d+)?,-?\d+(?:\.\d+)?$/'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
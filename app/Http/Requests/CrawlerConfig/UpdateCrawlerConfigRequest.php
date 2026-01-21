<?php

namespace App\Http\Requests\CrawlerConfig;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCrawlerConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:100'],
            'sources' => ['nullable', 'string', 'max:255'],
            'lexicon_id' => ['sometimes', 'uuid', 'exists:lexicons,id'],
            'description' => ['nullable', 'string'],
            'frequency_code' => ['sometimes', 'in:daily,weekly,monthly'],
            'status' => ['sometimes', 'in:enabled,disabled'],
        ];
    }
}

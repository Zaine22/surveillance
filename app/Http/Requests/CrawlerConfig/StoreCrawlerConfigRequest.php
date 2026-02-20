<?php

namespace App\Http\Requests\CrawlerConfig;

use Illuminate\Foundation\Http\FormRequest;

class StoreCrawlerConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'sources' => 'required|array|min:1',
            'sources.*' => 'required|string',
            'lexicon_id' => ['required', 'uuid', 'exists:lexicons,id'],
            'description' => ['nullable', 'string'],
            'frequency_code' => ['required', 'in:daily,weekly,monthly'],
            'status' => ['nullable', 'in:enabled,disabled'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after:from'],
        ];
    }
}

<?php

namespace App\Http\Requests\LexiconKeyword;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLexiconKeywordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'keywords' => 'sometimes|required|array|min:1',
            'keywords.*' => 'required|string|max:255',
            'crawl_hit_count' => 'integer|min:0',
            'case_count' => 'integer|min:0',
            'status' => 'in:enabled,disabled',
        ];
    }
}

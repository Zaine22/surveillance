<?php

namespace App\Http\Requests\Lexicon;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLexiconRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:100'],
            'remark' => ['nullable', 'string', 'max:500'],
            'status' => ['sometimes', 'in:enabled,disabled'],

            'keywords' => ['required', 'array'],

            'keywords.*.id' => ['required', 'string'],
            'keywords.*.keywords' => ['required', 'array', 'min:1'],
            'keywords.*.keywords.*' => ['string', 'min:1', 'max:100'],
            'keywords.*.status' => ['required', 'in:enabled,disabled'],
        ];
    }
}

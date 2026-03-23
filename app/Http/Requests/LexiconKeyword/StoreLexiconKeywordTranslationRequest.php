<?php
namespace App\Http\Requests\LexiconKeyword;

use Illuminate\Foundation\Http\FormRequest;

class StoreLexiconKeywordTranslationRequest extends FormRequest
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
            'keywords'   => ['required', 'array', 'min:1'],
            'keywords.*' => ['required', 'string', 'max:255'],
            'lang'       => ['required', 'in:zh,en,ja'],
        ];
    }

    public function messages(): array
    {
        return [
            'keywords.required'   => 'Keywords are required.',
            'keywords.array'      => 'Keywords must be an array.',
            'keywords.min'        => 'At least one keyword is required.',
            'keywords.*.required' => 'Each keyword cannot be empty.',
            'keywords.*.string'   => 'Each keyword must be a string.',
            'keywords.*.max'      => 'Each keyword must not exceed 255 characters.',
        ];
    }
}

<?php
namespace App\Http\Requests\LexiconKeyword;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            'keywords.required'   => '關鍵字為必填項。',
            'keywords.array'      => '關鍵字必須是陣列。',
            'keywords.min'        => '至少需要一個關鍵字。',
            'keywords.*.required' => '每個關鍵字不能為空。',
            'keywords.*.string'   => '每個關鍵字必須是字串。',
            'keywords.*.max'      => '每個關鍵字不得超過 255 個字元。',
            'lang.required'       => '語言為必填項。',
            'lang.in'             => '語言必須是以下其中之一：zh, en, ja。',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $keywords = $this->input('keywords', []);

            // Check for duplicates within the submitted keywords
            $normalizedKeywords = array_map(fn($k) => strtolower(trim($k)), $keywords);
            $uniqueKeywords = array_unique($normalizedKeywords);

            if (count($normalizedKeywords) !== count($uniqueKeywords)) {
                $validator->errors()->add(
                    'keywords',
                    '翻譯關鍵字中存在重複項。'
                );
            }
        });
    }
}

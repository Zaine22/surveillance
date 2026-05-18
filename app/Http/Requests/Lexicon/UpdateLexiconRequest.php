<?php

namespace App\Http\Requests\Lexicon;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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

            'keywords.*.id' => ['nullable', 'string'],
            'keywords.*.keywords' => ['required', 'array', 'min:1'],
            'keywords.*.keywords.*' => ['string', 'min:1', 'max:100'],
            'keywords.*.status' => ['required', 'in:enabled,disabled'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $keywordGroups = $this->input('keywords', []);
            $allKeywords = [];

            // Flatten all keywords from all groups
            foreach ($keywordGroups as $groupIndex => $group) {
                if (isset($group['keywords']) && is_array($group['keywords'])) {
                    foreach ($group['keywords'] as $keyword) {
                        $normalizedKeyword = strtolower(trim($keyword));

                        if (in_array($normalizedKeyword, $allKeywords)) {
                            $validator->errors()->add(
                                "keywords.{$groupIndex}.keywords",
                                "關鍵字 '{$keyword}' 在此詞庫中重複。"
                            );
                            return;
                        }

                        $allKeywords[] = $normalizedKeyword;
                    }
                }
            }
        });
    }
}
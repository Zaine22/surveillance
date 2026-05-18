<?php

namespace App\Http\Requests\Lexicon;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreLexiconRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100'],
            'remark' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', 'in:enabled,disabled'],
            'keywords' => ['required', 'array'],
            'keywords.*' => ['required', 'array'],
            'keywords.*.*' => ['required', 'string', 'min:1', 'max:100'],
            'case_management_id'  => ['nullable', 'exists:case_management,id'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $keywords = $this->input('keywords', []);
            $allKeywords = [];

            // Flatten all keywords from all groups
            foreach ($keywords as $groupIndex => $group) {
                if (is_array($group)) {
                    foreach ($group as $keyword) {
                        $normalizedKeyword = strtolower(trim($keyword));

                        if (in_array($normalizedKeyword, $allKeywords)) {
                            $validator->errors()->add(
                                "keywords.{$groupIndex}",
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

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

    public function messages(): array
    {
        return [
            'name.string'                  => '名稱必須是字串格式',
            'name.max'                     => '名稱不能超過 :max 個字元',
            'remark.string'                => '備註必須是字串格式',
            'remark.max'                   => '備註不能超過 :max 個字元',
            'status.in'                    => '狀態必須是以下其中之一：啟用、停用',
            'keywords.required'            => '關鍵字不能為空',
            'keywords.array'               => '關鍵字必須是陣列格式',
            'keywords.*.id.string'         => '關鍵字 ID 必須是字串格式',
            'keywords.*.keywords.required' => '關鍵字群組不能為空',
            'keywords.*.keywords.array'    => '關鍵字群組必須是陣列格式',
            'keywords.*.keywords.min'      => '關鍵字群組至少需要 :min 個關鍵字',
            'keywords.*.keywords.*.string' => '每個關鍵字必須是字串格式',
            'keywords.*.keywords.*.min'    => '每個關鍵字至少需要 :min 個字元',
            'keywords.*.keywords.*.max'    => '每個關鍵字不能超過 :max 個字元',
            'keywords.*.status.required'   => '關鍵字狀態不能為空',
            'keywords.*.status.in'         => '關鍵字狀態必須是以下其中之一：啟用、停用',
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

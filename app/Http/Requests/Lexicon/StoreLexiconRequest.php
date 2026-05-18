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

    public function messages(): array
    {
        return [
            'name.required'            => '名稱不能為空',
            'name.string'              => '名稱必須是字串格式',
            'name.max'                 => '名稱不能超過 :max 個字元',
            'remark.string'            => '備註必須是字串格式',
            'remark.max'               => '備註不能超過 :max 個字元',
            'status.in'                => '狀態必須是以下其中之一：啟用、停用',
            'keywords.required'        => '關鍵字不能為空',
            'keywords.array'           => '關鍵字必須是陣列格式',
            'keywords.*.required'      => '每個關鍵字群組不能為空',
            'keywords.*.array'         => '每個關鍵字群組必須是陣列格式',
            'keywords.*.*.required'    => '每個關鍵字不能為空',
            'keywords.*.*.string'      => '每個關鍵字必須是字串格式',
            'keywords.*.*.min'         => '每個關鍵字至少需要 :min 個字元',
            'keywords.*.*.max'         => '每個關鍵字不能超過 :max 個字元',
            'case_management_id.exists' => '案件管理 ID 不存在',
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
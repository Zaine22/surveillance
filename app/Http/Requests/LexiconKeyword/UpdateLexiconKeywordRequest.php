<?php

namespace App\Http\Requests\LexiconKeyword;

use App\Models\LexiconKeyword;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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

    public function messages(): array
    {
        return [
            'keywords.required'      => '關鍵字不能為空',
            'keywords.array'         => '關鍵字必須是陣列格式',
            'keywords.min'           => '至少需要 :min 個關鍵字',
            'keywords.*.required'    => '每個關鍵字不能為空',
            'keywords.*.string'      => '每個關鍵字必須是字串格式',
            'keywords.*.max'         => '每個關鍵字不能超過 :max 個字元',
            'crawl_hit_count.integer' => '爬蟲命中次數必須是整數',
            'crawl_hit_count.min'    => '爬蟲命中次數最小值為 :min',
            'case_count.integer'     => '案件數量必須是整數',
            'case_count.min'         => '案件數量最小值為 :min',
            'status.in'              => '狀態必須是以下其中之一：啟用、停用',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            if (!$this->has('keywords')) {
                return;
            }

            $keywords = $this->input('keywords', []);
            $keywordId = $this->route('lexicon_keyword') ?? $this->route('id');

            if (!$keywordId) {
                return;
            }

            // Get the current keyword record
            $currentKeyword = LexiconKeyword::find($keywordId);
            if (!$currentKeyword) {
                return;
            }

            // Get existing keywords for this lexicon (excluding current keyword group)
            $existingKeywords = LexiconKeyword::where('lexicon_id', $currentKeyword->lexicon_id)
                ->where('id', '!=', $keywordId)
                ->get()
                ->pluck('keywords')
                ->flatten()
                ->map(fn($k) => strtolower(trim($k)))
                ->toArray();

            // Check for duplicates
            if (is_array($keywords)) {
                foreach ($keywords as $keyword) {
                    if (is_string($keyword)) {
                        $normalizedKeyword = strtolower(trim($keyword));
                        if (in_array($normalizedKeyword, $existingKeywords)) {
                            $validator->errors()->add(
                                'keywords',
                                "關鍵字 '{$keyword}' 在此詞庫中已存在。"
                            );
                            return;
                        }
                    }
                }
            }
        });
    }
}

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
            'translations' => 'sometimes|nullable|array',
            'translations.zh' => 'sometimes|nullable|array',
            'translations.zh.*' => 'string|max:255',
            'translations.en' => 'sometimes|nullable|array',
            'translations.en.*' => 'string|max:255',
            'translations.ja' => 'sometimes|nullable|array',
            'translations.ja.*' => 'string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'translations.array'     => '翻譯必須是陣列格式',
            'translations.zh.array'  => '中文翻譯必須是陣列格式',
            'translations.zh.*.string'   => '每個中文翻譯關鍵字必須是字串格式',
            'translations.zh.*.max'      => '每個中文翻譯關鍵字不能超過 :max 個字元',
            'translations.en.array'  => '英文翻譯必須是陣列格式',
            'translations.en.*.string'   => '每個英文翻譯關鍵字必須是字串格式',
            'translations.en.*.max'      => '每個英文翻譯關鍵字不能超過 :max 個字元',
            'translations.ja.array'  => '日文翻譯必須是陣列格式',
            'translations.ja.*.string'   => '每個日文翻譯關鍵字必須是字串格式',
            'translations.ja.*.max'      => '每個日文翻譯關鍵字不能超過 :max 個字元',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $keywordId = $this->route('lexicon_keyword') ?? $this->route('id');

            if (!$keywordId) {
                return;
            }

            // Get the current keyword record
            $currentKeyword = LexiconKeyword::find($keywordId);
            if (!$currentKeyword) {
                return;
            }

            // Get existing keywords for this lexicon (excluding current keyword group and its translations)
            $existingKeywords = LexiconKeyword::where('lexicon_id', $currentKeyword->lexicon_id)
                ->where('id', '!=', $keywordId)
                ->where(function($query) use ($keywordId) {
                    // Exclude translations of the current keyword being edited
                    $query->where('parent_id', '!=', $keywordId)
                          ->orWhereNull('parent_id');
                })
                ->get()
                ->pluck('keywords')
                ->filter(fn($k) => is_array($k)) // Filter out non-array values
                ->flatten()
                ->map(fn($k) => strtolower(trim($k)))
                ->toArray();

            // Check for duplicates in translation keywords
            if ($this->has('translations')) {
                $translations = $this->input('translations', []);
                if (is_array($translations)) {
                    foreach ($translations as $language => $translationKeywords) {
                        if (is_array($translationKeywords)) {
                            foreach ($translationKeywords as $keyword) {
                                if (is_string($keyword)) {
                                    $normalizedKeyword = strtolower(trim($keyword));
                                    if (in_array($normalizedKeyword, $existingKeywords)) {
                                        $languageNames = [
                                            'zh' => '中文',
                                            'en' => '英文',
                                            'ja' => '日文'
                                        ];
                                        $langName = $languageNames[$language] ?? $language;
                                        $validator->errors()->add(
                                            "translations.{$language}",
                                            "{$langName}翻譯關鍵字 '{$keyword}' 在此詞庫中已存在。"
                                        );
                                        return;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        });
    }
}

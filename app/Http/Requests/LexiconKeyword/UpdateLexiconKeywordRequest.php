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
}
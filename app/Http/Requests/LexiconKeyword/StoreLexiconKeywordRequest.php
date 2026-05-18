<?php

namespace App\Http\Requests\LexiconKeyword;

use Illuminate\Foundation\Http\FormRequest;

class StoreLexiconKeywordRequest extends FormRequest
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
            'lexicon_id' => 'required|uuid|exists:lexicons,id',
            'keywords' => 'required|array|min:1',
            'keywords.*' => 'required',
            'crawl_hit_count' => 'integer|min:0',
            'case_count' => 'integer|min:0',
            'status' => 'in:enabled,disabled',
        ];
    }

    public function messages(): array
    {
        return [
            'lexicon_id.required'    => '詞庫 ID 不能為空',
            'lexicon_id.uuid'        => '詞庫 ID 必須是有效的 UUID 格式',
            'lexicon_id.exists'      => '詞庫 ID 不存在',
            'keywords.required'      => '關鍵字不能為空',
            'keywords.array'         => '關鍵字必須是陣列格式',
            'keywords.min'           => '至少需要 :min 個關鍵字',
            'keywords.*.required'    => '每個關鍵字不能為空',
            'crawl_hit_count.integer' => '爬蟲命中次數必須是整數',
            'crawl_hit_count.min'    => '爬蟲命中次數最小值為 :min',
            'case_count.integer'     => '案件數量必須是整數',
            'case_count.min'         => '案件數量最小值為 :min',
            'status.in'              => '狀態必須是以下其中之一：啟用、停用',
        ];
    }
}
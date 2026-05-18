<?php
namespace App\Http\Requests\CrawlerConfig;

use Illuminate\Foundation\Http\FormRequest;

class StoreCrawlerConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'max:100'],
            'sources'        => 'required|array|min:1',
            'sources.*'      => 'required|string',
            'lexicon_id'     => ['required', 'uuid', 'exists:lexicons,id'],
            'description'    => ['nullable', 'string'],
            'frequency_code' => ['required', 'in:daily,weekly,monthly'],
            'status'         => ['nullable', 'in:enabled,disabled'],
            'from'           => ['nullable', 'date'],
            'to'             => ['nullable', 'date', 'after:from'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'         => '名稱不能為空',
            'name.string'           => '名稱必須是字串格式',
            'name.max'              => '名稱不能超過 :max 個字元',
            'sources.required'      => '來源不能為空',
            'sources.array'         => '來源必須是陣列格式',
            'sources.min'           => '至少需要 :min 個來源',
            'sources.*.required'    => '每個來源不能為空',
            'sources.*.string'      => '每個來源必須是字串格式',
            'lexicon_id.required'   => '詞庫 ID 不能為空',
            'lexicon_id.uuid'       => '詞庫 ID 必須是有效的 UUID 格式',
            'lexicon_id.exists'     => '詞庫 ID 不存在',
            'description.string'    => '描述必須是字串格式',
            'frequency_code.required' => '頻率代碼不能為空',
            'frequency_code.in'     => '頻率代碼必須是以下其中之一：每日、每週、每月',
            'status.in'             => '狀態必須是以下其中之一：啟用、停用',
            'from.date'             => '起始日期必須是有效的日期格式',
            'to.date'               => '結束日期必須是有效的日期格式',
            'to.after'              => '結束日期必須在起始日期之後',
        ];
    }
}
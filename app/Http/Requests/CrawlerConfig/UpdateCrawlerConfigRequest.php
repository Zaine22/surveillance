<?php
namespace App\Http\Requests\CrawlerConfig;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCrawlerConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'           => ['sometimes', 'string', 'max:100'],
            'sources'        => 'sometimes|array|min:1',
            'sources.*'      => 'required|string',
            'lexicon_id'     => ['sometimes', 'uuid', 'exists:lexicons,id'],
            'description'    => ['nullable', 'string'],
            'frequency_code' => ['sometimes', 'in:daily,weekly,monthly'],
            'from'           => ['nullable', 'date'],
            'to'             => ['nullable', 'date', 'after:from'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.string'           => '名稱必須是字串格式',
            'name.max'              => '名稱不能超過 :max 個字元',
            'sources.array'         => '來源必須是陣列格式',
            'sources.min'           => '至少需要 :min 個來源',
            'sources.*.required'    => '每個來源不能為空',
            'sources.*.string'      => '每個來源必須是字串格式',
            'lexicon_id.uuid'       => '詞庫 ID 必須是有效的 UUID 格式',
            'lexicon_id.exists'     => '詞庫 ID 不存在',
            'description.string'    => '描述必須是字串格式',
            'frequency_code.in'     => '頻率代碼必須是以下其中之一：每日、每週、每月',
            'from.date'             => '起始日期必須是有效的日期格式',
            'to.date'               => '結束日期必須是有效的日期格式',
            'to.after'              => '結束日期必須在起始日期之後',
        ];
    }
}

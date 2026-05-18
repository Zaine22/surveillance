<?php
namespace App\Http\Requests\AiModelTask;

use Illuminate\Foundation\Http\FormRequest;

class AiModelTaskIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert comma-separated string to array if status is a string
        if ($this->has('status') && is_string($this->status)) {
            $this->merge([
                'status' => array_map('trim', explode(',', $this->status)),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search'          => 'nullable|string',
            'status'          => 'nullable|array',
            'status.*'        => 'string|in:pending,processing,completed',
            'analysis_result' => 'nullable|string',
            'range'           => 'nullable|string',
            'from_date'       => 'nullable|date',
            'to_date'         => 'nullable|date',
            'per_page'        => 'nullable|integer|min:1|max:100',
            'page'            => 'nullable|integer|min:1',
            'sort_by'         => 'nullable|string',
            'sort_order'      => 'nullable|string|in:asc,desc',
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'search.string'          => '搜尋欄位必須是字串格式',
            'status.array'           => '狀態欄位必須是陣列格式',
            'status.*.string'        => '狀態值必須是字串格式',
            'status.*.in'            => '狀態值必須是以下其中之一：待處理、處理中、已完成',
            'analysis_result.string' => '分析結果欄位必須是字串格式',
            'range.string'           => '範圍欄位必須是字串格式',
            'from_date.date'         => '起始日期必須是有效的日期格式',
            'to_date.date'           => '結束日期必須是有效的日期格式',
            'per_page.integer'       => '每頁筆數必須是整數',
            'per_page.min'           => '每頁筆數最少為 :min 筆',
            'per_page.max'           => '每頁筆數最多為 :max 筆',
            'page.integer'           => '頁碼必須是整數',
            'page.min'               => '頁碼最小值為 :min',
            'sort_by.string'         => '排序欄位必須是字串格式',
            'sort_order.string'      => '排序方式必須是字串格式',
            'sort_order.in'          => '排序方式必須是以下其中之一：升冪（asc）、降冪（desc）',
        ];
    }
}

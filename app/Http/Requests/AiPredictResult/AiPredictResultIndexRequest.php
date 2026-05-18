<?php
namespace App\Http\Requests\AiPredictResult;

use Illuminate\Foundation\Http\FormRequest;

class AiPredictResultIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('review_status')) {

            $map = [
                'reviewed' => 'approved',
                'pending'  => 'pending',
                'rejected' => 'rejected',
            ];

            $this->merge([
                'review_status' => $map[$this->review_status] ?? $this->review_status,
            ]);
        }

        if ($this->has('analysis_result')) {
            $this->merge([
                'ai_analysis_result' => strtolower(trim($this->analysis_result)),
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
            'search'             => ['nullable', 'string'],
            'review_status'      => ['nullable', 'in:pending,approved,rejected'],
            'ai_analysis_result' => ['nullable', 'in:normal,abnormal'],
            'range'              => ['nullable', 'in:one_week,one_month,one_year'],
            'from_date'          => ['nullable', 'date'],
            'to_date'            => ['nullable', 'date'],
            'page'               => ['nullable', 'integer'],
            'per_page'           => ['nullable', 'integer'],
            'sort_by'            => ['nullable', 'string'],
            'sort_order'         => ['nullable', 'in:asc,desc'],
        ];
    }

    public function messages(): array
    {
        return [
            'search.string'             => '搜尋欄位必須是字串格式',
            'review_status.in'          => '審核狀態必須是以下其中之一：待審核、已批准、已拒絕',
            'ai_analysis_result.in'     => 'AI 分析結果必須是以下其中之一：正常、異常',
            'range.in'                  => '範圍必須是以下其中之一：一週、一個月、一年',
            'from_date.date'            => '起始日期必須是有效的日期格式',
            'to_date.date'              => '結束日期必須是有效的日期格式',
            'page.integer'              => '頁碼必須是整數',
            'per_page.integer'          => '每頁筆數必須是整數',
            'sort_by.string'            => '排序欄位必須是字串格式',
            'sort_order.in'             => '排序方式必須是以下其中之一：升冪（asc）、降冪（desc）',
        ];
    }
}

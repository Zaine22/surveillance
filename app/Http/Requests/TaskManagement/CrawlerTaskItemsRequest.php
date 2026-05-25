<?php
namespace App\Http\Requests\TaskManagement;

use Illuminate\Foundation\Http\FormRequest;

class CrawlerTaskItemsRequest extends FormRequest
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
            'search'     => ['nullable', 'string', 'max:100'],

            'status'     => [
                'nullable',
                'in:pending,running,completed,failed,paused',
            ],

            'page'       => ['nullable', 'integer', 'min:1'],
            'per_page'   => ['nullable', 'integer', 'min:1', 'max:100'],

            'sort_by'    => [
                'nullable',
                'in:created_at,updated_at,status',
            ],

            'sort_order' => [
                'nullable',
                'in:asc,desc',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'search.string'    => '搜尋欄位必須是字串格式',
            'search.max'       => '搜尋欄位不能超過 :max 個字元',
            'status.in'        => '狀態必須是以下其中之一：待處理、執行中、已完成、失敗、已暫停',
            'page.integer'     => '頁碼必須是整數',
            'page.min'         => '頁碼最小值為 :min',
            'per_page.integer' => '每頁筆數必須是整數',
            'per_page.min'     => '每頁筆數最少為 :min 筆',
            'per_page.max'     => '每頁筆數最多為 :max 筆',
            'sort_by.in'       => '排序欄位必須是以下其中之一：建立時間、更新時間、狀態',
            'sort_order.in'    => '排序方式必須是以下其中之一：升冪（asc）、降冪（desc）',
        ];
    }

    public function validated($key = null, $default = null): array
    {
        $data = parent::validated();

        // Map frontend status values to database status values
        if (isset($data['status'])) {
            $data['status'] = match ($data['status']) {
                'running'   => ['crawling', 'syncing'],
                'completed' => 'synced',
                'failed'    => 'error',
                default     => $data['status'],
            };
        }

        return $data;
    }
}

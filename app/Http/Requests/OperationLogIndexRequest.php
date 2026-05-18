<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OperationLogIndexRequest extends FormRequest
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
            'department' => [
                'nullable',
                'string',
                'exists:departments,name',
            ],
            'action'     => ['nullable', 'string'],
            'search'     => ['nullable', 'string', 'max:100'],
            'range'      => ['nullable', 'in:one_week,one_month,one_year'],
            'from_date'  => ['nullable', 'date'],
            'to_date'    => ['nullable', 'date', 'after_or_equal:from_date'],
            'page'       => ['nullable', 'integer', 'min:1'],
            'per_page'   => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_by'    => ['nullable', 'string'],
            'sort_order' => ['nullable', 'in:asc,desc'],
        ];
    }

    public function messages(): array
    {
        return [
            'department.string'       => '部門必須是字串格式',
            'department.exists'       => '部門不存在',
            'action.string'           => '操作必須是字串格式',
            'search.string'           => '搜尋欄位必須是字串格式',
            'search.max'              => '搜尋欄位不能超過 :max 個字元',
            'range.in'                => '範圍必須是以下其中之一：一週、一個月、一年',
            'from_date.date'          => '起始日期必須是有效的日期格式',
            'to_date.date'            => '結束日期必須是有效的日期格式',
            'to_date.after_or_equal'  => '結束日期必須在起始日期之後或相同',
            'page.integer'            => '頁碼必須是整數',
            'page.min'                => '頁碼最小值為 :min',
            'per_page.integer'        => '每頁筆數必須是整數',
            'per_page.min'            => '每頁筆數最少為 :min 筆',
            'per_page.max'            => '每頁筆數最多為 :max 筆',
            'sort_by.string'          => '排序欄位必須是字串格式',
            'sort_order.in'           => '排序方式必須是以下其中之一：升冪（asc）、降冪（desc）',
        ];
    }
}

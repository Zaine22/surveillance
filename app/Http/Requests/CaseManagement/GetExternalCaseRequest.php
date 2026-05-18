<?php

namespace App\Http\Requests\CaseManagement;

use Illuminate\Foundation\Http\FormRequest;

class GetExternalCaseRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:立案,成案,待截圖,截圖完成,不成案,全部'],
            'dateRange' => ['nullable', 'string', 'in:一週,一個月,一年,自行選擇範圍'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_by' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'in:asc,desc'],
        ];
    }

    public function messages(): array
    {
        return [
            'search.string'        => '搜尋欄位必須是字串格式',
            'search.max'           => '搜尋欄位不能超過 :max 個字元',
            'status.string'        => '狀態必須是字串格式',
            'status.in'            => '狀態必須是以下其中之一：立案、成案、待截圖、截圖完成、不成案、全部',
            'dateRange.string'     => '日期範圍必須是字串格式',
            'dateRange.in'         => '日期範圍必須是以下其中之一：一週、一個月、一年、自行選擇範圍',
            'from.date'            => '起始日期必須是有效的日期格式',
            'to.date'              => '結束日期必須是有效的日期格式',
            'to.after_or_equal'    => '結束日期必須在起始日期之後或相同',
            'page.integer'         => '頁碼必須是整數',
            'page.min'             => '頁碼最小值為 :min',
            'per_page.integer'     => '每頁筆數必須是整數',
            'per_page.min'         => '每頁筆數最少為 :min 筆',
            'per_page.max'         => '每頁筆數最多為 :max 筆',
            'sort_by.string'       => '排序欄位必須是字串格式',
            'sort_order.in'        => '排序方式必須是以下其中之一：升冪（asc）、降冪（desc）',
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new \Illuminate\Validation\ValidationException($validator, response()->json([
            // 'message' => 'The given data was invalid.',
            'message' => '提供的資料無效。',
            'errors' => $validator->errors(),
        ], 422));
    }
}
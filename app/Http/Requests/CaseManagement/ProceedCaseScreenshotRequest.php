<?php

namespace App\Http\Requests\CaseManagement;

use Illuminate\Foundation\Http\FormRequest;

class ProceedCaseScreenshotRequest extends FormRequest
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
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'case_id' => 'required|string|exists:case_management,external_case_no',
            'url' => 'required|url',
        ];
    }

    public function messages(): array
    {
        return [
            'issue_date.required' => '案件發佈日期不能為空',
            'issue_date.date' => '案件發佈日期必須為有效日期',
            'due_date.required' => '案件截止日期不能為空',
            'due_date.date' => '案件截止日期必須為有效日期',
            'due_date.after_or_equal' => '案件截止日期必須在案件發佈日期之後或相同',
            'case_id.required' => '案件編號不能為空',
            'case_id.exists' => '案件編號不存在',
            'url.required' => 'URL不能為空',
            'url.url' => 'URL格式不正確',
        ];
    }
}

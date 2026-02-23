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
            'url' => 'required|url',
        ];
    }

    public function messages(): array
    {
        return [
            'issue_date.required' => '案件发布日期不能为空',
            'issue_date.date' => '案件发布日期必须是有效的日期',
            'due_date.required' => '案件截止日期不能为空',
            'due_date.date' => '案件截止日期必须是有效的日期',
            'due_date.after_or_equal' => '案件截止日期必须在案件发布日期之后或相同',
            'url.required' => 'URL不能为空',
            'url.url' => 'URL格式不正确',
        ];
    }
}
<?php

namespace App\Http\Requests\CaseManagement;

use Illuminate\Foundation\Http\FormRequest;

class StoreCaseFeedbackRequest extends FormRequest
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
            'case_id' => 'required|string',
            'url' => 'required|url',
            'is_illegal' => 'required|boolean',
            'legal_basis' => 'nullable|string',
            'reason' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'case_id.required' => '案件ID不能為空',
            'url.required' => 'URL不能為空',
            'url.url' => 'URL格式不正確',
            'is_illegal.required' => '請指定是否違法',
            'is_illegal.boolean' => 'is_illegal 必須為布林值',
        ];
    }
}

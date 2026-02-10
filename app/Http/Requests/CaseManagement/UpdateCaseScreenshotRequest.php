<?php

namespace App\Http\Requests\CaseManagement;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCaseScreenshotRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'media_url' => 'required|url',
            'status' => 'nullable|in:valid,invalid',
        ];
    }

    /**
     * to validate route parameter and merge into request data
     */
    public function prepareForValidation(): void
    {
        $this->merge([
            'case_item_id' => $this->route('caseItemId'),
        ]);
    }

    public function messages(): array
    {
        return [
            'media_url.required' => '媒体URL不能为空',
            'media_url.url' => '媒体URL格式不正确',
            'status.in' => '状态必须是 valid 或 invalid',
        ];
    }
}

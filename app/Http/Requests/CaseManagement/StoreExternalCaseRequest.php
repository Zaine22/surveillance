<?php

namespace App\Http\Requests\CaseManagement;

use Illuminate\Foundation\Http\FormRequest;

class StoreExternalCaseRequest extends FormRequest
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
            'url' => 'required|url',
            'leakReason' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'url.required' => 'URL不能为空',
            'url.url' => 'URL格式不正确',
            'leakReason.required' => '泄露原因不能为空',
            'leakReason.string' => '泄露原因必须是字符串',
        ];
    }
}

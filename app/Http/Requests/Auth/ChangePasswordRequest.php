<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
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
            'current_password' => 'required',
            'new_password' => [
                'required',
                'string',
                'min:12',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]+$/'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => '当前密碼不能為空',
            'new_password.required' => '新密碼不能為空',
            'new_password.string' => '新密碼必須為字串',
            'new_password.min' => '新密碼至少需要12個字元',
            'new_password.regex' => '新密碼必須包含大小寫英文字母、數字及符號(@$!%*?&#)',
        ];
    }
}

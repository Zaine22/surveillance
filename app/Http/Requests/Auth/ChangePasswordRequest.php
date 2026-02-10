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
            'new_password' => 'required|string|min:8',
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => '当前密码不能为空',
            'new_password.required' => '新密码不能为空',
            'new_password.string' => '新密码必须是字符串',
            'new_password.min' => '新密码必须至少8个字符',
        ];
    }
}

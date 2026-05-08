<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterUserRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'department' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '使用者名稱不能為空',
            'name.string' => '使用者名稱必須為字串',
            'name.max' => '使用者名稱不能超過255個字元',
            'email.required' => '電子郵件不能為空',
            'email.email' => '電子郵件格式不正確',
            'email.unique' => '電子郵件已被註冊',
            'password.required' => '密碼不能為空',
            'password.string' => '密碼必須為字串',
            'password.min' => '密碼至少需要8個字元',
            'department.string' => '部門必須為字串',
        ];
    }
}

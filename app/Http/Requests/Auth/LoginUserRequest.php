<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginUserRequest extends FormRequest
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
            'email' => 'required|email:rfc,dns',
            'password' => 'required|string',
            'otp' => 'nullable|string|size:6',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => '電子郵件不能為空',
            'email.email' => '電子郵件格式不正確',
            'password.required' => '密碼不能為空',
            'password.string' => '密碼必須為字串',
            // 'password.min' => '密碼至少需要8個字元',
            'otp.string' => 'OTP必須為字串',
            'otp.size' => 'OTP必須為6位數',
        ];
    }
}

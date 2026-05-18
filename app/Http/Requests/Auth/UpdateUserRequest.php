<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
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
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,'.$this->user()->id,
            'department' => 'sometimes|nullable|string',
            'roles' => 'sometimes|nullable|string',
            'phone' => 'sometimes|nullable|string',
            'status' => 'sometimes|nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'   => '使用者名稱不能為空',
            'name.string'     => '使用者名稱必須為字串',
            'name.max'        => '使用者名稱不能超過 :max 個字元',
            'email.required'  => '電子郵件不能為空',
            'email.email'     => '電子郵件格式不正確',
            'email.unique'    => '電子郵件已被註冊',
            'department.string' => '部門必須為字串',
            'roles.string'    => '角色必須為字串',
            'phone.string'    => '電話必須為字串',
            'status.string'   => '狀態必須為字串',
        ];
    }
}
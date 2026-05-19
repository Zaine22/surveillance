<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
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
            'name' => 'required|string|max:20',
            'email' => 'required|email|unique:users,email',
            'department' => [
                'nullable',
                'string',
                'exists:departments,name',
            ],
            'roles' => 'required|string',
            'status' => 'required|string',
            'phone' => 'nullable|string',
        ];
    }

    protected function prepareForValidation(): void
    {
        $user = $this->user();

        if (! $user || $user->roles !== '管理員') {
            throw new AuthorizationException(
                // 'You are not allowed to perform this action.'
                '您沒有權限執行此操作。'
            );
        }
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
            'role.required' => '角色不能為空',
            'role.string' => '角色必須為字串',
            'department.string' => '部門必須為字串',
            'roles.required' => '角色不能為空',
            'roles.string' => '角色必須為字串',
            'status.required' => '狀態不能為空',
            'status.string' => '狀態必須為字串',
            'phone.string' => '電話必須為字串',
            'phone.nullable' => '電話可以為空',
        ];
    }
}

<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class SendOtpRequest extends FormRequest
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
            'email' => [
                'required',
                'email:rfc,dns',
                function ($attribute, $value, $fail) {
                    $domain = strtolower(substr(strrchr($value, "@"), 1));

                    $blockedDomains = [
                        'gmail.co',
                        'gamil.com',
                        'gmial.com',
                        'gnail.com',
                    ];

                    if (in_array($domain, $blockedDomains, true)) {
                        $fail('邮箱格式不正确');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => '邮箱不能为空',
            'email.email' => '邮箱格式不正确',
        ];
    }
}

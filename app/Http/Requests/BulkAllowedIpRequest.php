<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkAllowedIpRequest extends FormRequest
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
            'records' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            // 'records.required' => 'IP records are required.',
            'records.required' => '需要 IP 記錄。',
            // 'records.string'   => 'Records must be text.',
            'records.string'   => '記錄必須為文字格式。',
        ];
    }
}

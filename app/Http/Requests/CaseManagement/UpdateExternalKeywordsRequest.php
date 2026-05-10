<?php

namespace App\Http\Requests\CaseManagement;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExternalKeywordsRequest extends FormRequest
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
            'case_id' => 'required|string|max:100',
            'keywords' => 'required|array',
        ];
    }

    public function messages(): array
    {
        return [
            'case_id.required' => '案件編號不能為空',
            'case_id.string' => '案件編號必須為字串',
            'case_id.max' => '案件編號不能超過100個字元',
            'keywords.required' => '關鍵字不能為空',
            'keywords.array' => '關鍵字必須為陣列格式',
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new \Illuminate\Validation\ValidationException($validator, response()->json([
            'message' => 'The given data was invalid.',
            'errors' => $validator->errors(),
        ], 422));
    }
}

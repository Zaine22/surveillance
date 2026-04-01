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
            'case_id.required' => '案件编号不能为空',
            'case_id.string' => '案件编号必须是字符串',
            'case_id.max' => '案件编号不能超过100个字符',
            'keywords.required' => '关键字不能为空',
            'keywords.array' => '关键字必须是数组格式',
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

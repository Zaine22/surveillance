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
            // 'keywords' => 'required|array',
            'external_lexicon_id' => 'required|string|max:100'
        ];
    }

    public function messages(): array
    {
        return [
            'case_id.required' => '案件編號不能為空',
            'case_id.string' => '案件編號必須為字串',
            'case_id.max' => '案件編號不能超過100個字元',
            'external_lexicon_id.required' => '詞庫編號不能為空',
            'external_lexicon_id.string' => '詞庫編號必須為字串',
            'external_lexicon_id.max' => '詞庫編號不能超過100個字元',
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

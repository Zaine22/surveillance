<?php
namespace App\Http\Requests\CaseManagement;

use Illuminate\Foundation\Http\FormRequest;

class StoreExternalCaseRequest extends FormRequest
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
            'url'        => 'required|url',
            'leakReason' => 'required|string',
            'case_id'    => 'required|string|max:100',
            'issue_date' => 'nullable|date',
            'due_date'   => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'url.required'        => 'URL不能为空',
            'url.url'             => 'URL格式不正确',
            'leakReason.required' => '泄露原因不能为空',
            'leakReason.string'   => '泄露原因必须是字符串',
            'case_id.required'    => '案件编号不能为空',
            'case_id.string'      => '案件编号必须是字符串',
            'issue_date.date'     => '发布日期格式不正确',
            'due_date.date'       => '到期日期格式不正确',
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new \Illuminate\Validation\ValidationException($validator, response()->json([
            'message' => 'The given data was invalid.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
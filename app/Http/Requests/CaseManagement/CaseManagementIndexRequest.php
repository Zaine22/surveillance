<?php
namespace App\Http\Requests\CaseManagement;

use Illuminate\Foundation\Http\FormRequest;

class CaseManagementIndexRequest extends FormRequest
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
            'search'     => ['nullable', 'string', 'max:255'],
            'status'     => [
                'nullable',
                'string',
                'in:pending_notification,notified,case_established,case_not_established,tracking_completed,external_pending',
            ],
            'range'  => ['nullable', 'string', 'in:one_week,one_month,one_year'],
            'from_date'       => ['nullable', 'date'],
            'to_date'         => ['nullable', 'date', 'after_or_equal:from_date'],
            'page'       => ['nullable', 'integer', 'min:1'],
            'per_page'   => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_by'    => ['nullable', 'string'],
            'sort_order' => ['nullable', 'in:asc,desc'],
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

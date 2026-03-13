<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OperationLogIndexRequest extends FormRequest
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
            'department' => ['nullable', 'string'],
            'search'     => ['nullable', 'string', 'max:100'],
            'range'      => ['nullable', 'in:one_week,one_month,one_year'],
            'from_date'  => ['nullable', 'date'],
            'to_date'    => ['nullable', 'date', 'after_or_equal:from_date'],
            'page'       => ['nullable', 'integer', 'min:1'],
            'per_page'   => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_by'    => ['nullable', 'string'],
            'sort_order' => ['nullable', 'in:asc,desc'],
        ];
    }
}

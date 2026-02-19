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
            'search'      => ['nullable', 'string'],
            'status'      => ['nullable', 'in:pending,created,notified,moved_offline,auto_offline'],
            'range'       => ['nullable', 'in:one_week,one_month,one_year'],
            'from_date'   => ['nullable', 'date'],
            'to_date'     => ['nullable', 'date'],
            'page'        => ['nullable', 'integer'],
            'per_page'    => ['nullable', 'integer'],
            'sort_by'     => ['nullable', 'string'],
            'sort_order'  => ['nullable', 'in:asc,desc'],
        ];
    }
}

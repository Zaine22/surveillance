<?php

namespace App\Http\Requests\AiPredictResult;

use Illuminate\Foundation\Http\FormRequest;

class AiPredictResultIndexRequest extends FormRequest
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
            'search'              => ['nullable', 'string'],
            'review_status'       => ['nullable', 'in:pending,approved,rejected'],
            'audit_status'        => ['nullable', 'in:pending,approved,rejected'],
            'range'               => ['nullable', 'in:one_week,one_month,one_year'],
            'from_date'           => ['nullable', 'date'],
            'to_date'             => ['nullable', 'date'],
            'page'                => ['nullable', 'integer'],
            'per_page'            => ['nullable', 'integer'],
            'sort_by'             => ['nullable', 'string'],
            'sort_order'          => ['nullable', 'in:asc,desc'],
        ];
    }
}

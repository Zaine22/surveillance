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

    protected function prepareForValidation(): void
    {
        if ($this->has('review_status')) {

            $map = [
                'reviewed' => 'approved',
                'pending'  => 'pending',
                'rejected' => 'rejected',
            ];

            $this->merge([
                'review_status' => $map[$this->review_status] ?? $this->review_status,
            ]);
        }

        if ($this->has('analysis_result')) {
            $this->merge([
                'ai_analysis_result' => strtolower(trim($this->analysis_result)),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search'             => ['nullable', 'string'],
            'review_status'      => ['nullable', 'in:pending,approved,rejected'],
            'ai_analysis_result' => ['nullable', 'in:normal,abnormal'],
            'range'              => ['nullable', 'in:one_week,one_month,one_year'],
            'from_date'          => ['nullable', 'date'],
            'to_date'            => ['nullable', 'date'],
            'page'               => ['nullable', 'integer'],
            'per_page'           => ['nullable', 'integer'],
            'sort_by'            => ['nullable', 'string'],
            'sort_order'         => ['nullable', 'in:asc,desc'],
        ];
    }
}

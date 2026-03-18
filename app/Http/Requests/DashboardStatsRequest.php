<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DashboardStatsRequest extends FormRequest
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
            'range'     => 'nullable|in:today,one_week,one_month,this_week,this_month,one_year',
            'from_date' => 'nullable|date',
            'to_date'   => 'nullable|date',
        ];
    }
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            if ($this->filled('range') && ($this->filled('from_date') || $this->filled('to_date'))) {
                $validator->errors()->add('range', 'Do not provide range together with from_date/to_date.');
            }

            if ($this->filled('from_date') && ! $this->filled('to_date')) {
                $validator->errors()->add('to_date', 'to_date is required when from_date is provided.');
            }

            if (! $this->filled('from_date') && $this->filled('to_date')) {
                $validator->errors()->add('from_date', 'from_date is required when to_date is provided.');
            }
        });
    }
}

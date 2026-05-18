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
            'limit'     => 'nullable|integer|min:1|max:50',
            'offset'    => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'range.in'        => '範圍必須是以下其中之一：今天、一週、一個月、本週、本月、一年',
            'from_date.date'  => '起始日期必須是有效的日期格式',
            'to_date.date'    => '結束日期必須是有效的日期格式',
            'limit.integer'   => '限制數量必須是整數',
            'limit.min'       => '限制數量最少為 :min',
            'limit.max'       => '限制數量最多為 :max',
            'offset.integer'  => '偏移量必須是整數',
            'offset.min'      => '偏移量最小值為 :min',
        ];
    }
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            if ($this->filled('range') && ($this->filled('from_date') || $this->filled('to_date'))) {
                // $validator->errors()->add('range', 'Do not provide range together with from_date/to_date.');
                 $validator->errors()->add('range', '請勿同時提供範圍與起始日期／結束日期。');
            }

            if ($this->filled('from_date') && ! $this->filled('to_date')) {
                // $validator->errors()->add('to_date', 'to_date is required when from_date is provided.');
                $validator->errors()->add('to_date', '若提供起始日期，則必須提供結束日期。');
            }

            if (! $this->filled('from_date') && $this->filled('to_date')) {
                // $validator->errors()->add('from_date', 'from_date is required when to_date is provided.');
                 $validator->errors()->add('from_date', '若提供結束日期，則必須提供起始日期。');
            }
        });
    }
}
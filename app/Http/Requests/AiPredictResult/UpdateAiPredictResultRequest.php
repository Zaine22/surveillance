<?php
namespace App\Http\Requests\AiPredictResult;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAiPredictResultRequest extends FormRequest
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
            'items'                => ['required', 'array'],

            'items.*.id'           => [
                'required',
                'uuid',
                'exists:ai_predict_result_items,id',
            ],

            'items.*.decision'     => [
                'required',
                'in:valid,invalid',
            ],

            'items.*.reason'       => [
                'nullable',
                'string',
            ],

            'items.*.other_reason' => [
                'nullable',
                'string',
            ],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            foreach ($this->items ?? [] as $index => $item) {

                if ($item['decision'] === 'invalid') {

                    if (empty($item['reason'])) {
                        // $validator->errors()->add(
                        //     "items.$index.reason",
                        //     'Review comment is required when decision is invalid.'
                        // );
                        $validator->errors()->add(
                            "items.$index.reason",
                            '當審核決定為「無效」時，必須填寫審核備註。'
                        );
                    }

                    if (
                        ($item['reason'] ?? null) === 'Other'
                        && empty($item['other_reason'])
                    ) {
                        // $validator->errors()->add(
                        //     "items.$index.other_reason",
                        //     'Other reason is required.'
                        // );
                        $validator->errors()->add(
                            "items.$index.other_reason",
                            '必須填寫其他原因。'
                        );
                    }
                }
            }
        });
    }
}

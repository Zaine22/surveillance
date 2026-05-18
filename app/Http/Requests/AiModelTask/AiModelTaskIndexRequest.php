<?php
namespace App\Http\Requests\AiModelTask;

use Illuminate\Foundation\Http\FormRequest;

class AiModelTaskIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert comma-separated string to array if status is a string
        if ($this->has('status') && is_string($this->status)) {
            $this->merge([
                'status' => array_map('trim', explode(',', $this->status))
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
            'search'          => 'nullable|string',
            'status'          => 'nullable|array',
            'status.*'        => 'string|in:pending,processing,completed',
            'analysis_result' => 'nullable|string',
            'range'           => 'nullable|string',
            'from_date'       => 'nullable|date',
            'to_date'         => 'nullable|date',
            'per_page'        => 'nullable|integer|min:1|max:100',
            'page'            => 'nullable|integer|min:1',
            'sort_by'         => 'nullable|string',
            'sort_order'      => 'nullable|string|in:asc,desc',
        ];
    }
}
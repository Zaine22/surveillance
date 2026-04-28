<?php
namespace App\Http\Requests\TaskManagement;

use Illuminate\Foundation\Http\FormRequest;

class CrawlerTaskIndexRequest extends FormRequest
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
            'search'     => ['nullable', 'string', 'max:100'],
            'status'     => [
                'nullable',
                'in:pending,running,completed,failed,paused',
            ],
            'range'      => [
                'nullable',
                'in:one_week,one_month,one_year',
            ],
            'from_date'  => ['nullable', 'date_format:Y-m-d'],
            'to_date'    => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from_date'],
            'page'       => ['nullable', 'integer', 'min:1'],
            'per_page'   => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_by'    => [
                'nullable',
                'in:created_at,updated_at,status',
            ],
            'sort_order' => [
                'nullable',
                'in:asc,desc',
            ],
        ];
    }

    public function validated($key = null, $default = null): array
    {
        $data = parent::validated();

        if (isset($data['status'])) {
            $data['status'] = match ($data['status']) {
                'running' => 'processing',
                'failed'  => 'error',
                default   => $data['status'],
            };
        }

        return $data;
    }
}

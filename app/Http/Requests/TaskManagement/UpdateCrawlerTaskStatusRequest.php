<?php
namespace App\Http\Requests\TaskManagement;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCrawlerTaskStatusRequest extends FormRequest
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
            'action' => ['required', 'string', 'in:pause,resume,delete'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'Action is required.',
            'action.in'       => 'Action must be pause, resume, or delete.',
        ];
    }
}
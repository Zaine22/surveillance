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
            'action.required' => '操作不能為空',
            'action.in'       => '操作必須是以下其中之一：暫停、繼續、刪除',
        ];
    }
}

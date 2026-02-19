<?php

namespace App\Http\Requests\SystemNotice;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNoticeRequest extends FormRequest
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
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'status' => 'nullable|string|in:published,pending',
            'publish_date' => 'nullable|date',
            'expire_at' => 'nullable|date|after:published_date',
        ];
    }
}

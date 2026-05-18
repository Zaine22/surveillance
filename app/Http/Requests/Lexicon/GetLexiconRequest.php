<?php

namespace App\Http\Requests\Lexicon;

use Illuminate\Foundation\Http\FormRequest;

class GetLexiconRequest extends FormRequest
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
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'search' => 'nullable|string',
            'status' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'page.integer'     => '頁碼必須是整數',
            'page.min'         => '頁碼最小值為 :min',
            'per_page.integer' => '每頁筆數必須是整數',
            'per_page.min'     => '每頁筆數最少為 :min 筆',
            'per_page.max'     => '每頁筆數最多為 :max 筆',
            'search.string'    => '搜尋欄位必須是字串格式',
            'status.string'    => '狀態必須是字串格式',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'page' => $this->page ?? 1,
            'perPage' => $this->perPage ?? 15,
        ]);
    }
}

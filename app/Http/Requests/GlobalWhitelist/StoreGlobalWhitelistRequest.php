<?php

namespace App\Http\Requests\GlobalWhitelist;

use Illuminate\Foundation\Http\FormRequest;

class StoreGlobalWhitelistRequest extends FormRequest
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
            'url' => ['required', 'array', 'min:1'],
            'url.*' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'url.required'   => 'URL 不能為空',
            'url.array'      => 'URL 必須是陣列格式',
            'url.min'        => '至少需要 :min 個 URL',
            'url.*.required' => '每個 URL 不能為空',
            'url.*.string'   => '每個 URL 必須是字串格式',
            'url.*.max'      => '每個 URL 不能超過 :max 個字元',
        ];
    }
}

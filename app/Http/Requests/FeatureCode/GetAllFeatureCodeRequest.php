<?php
namespace App\Http\Requests\FeatureCode;

use Illuminate\Foundation\Http\FormRequest;

class GetAllFeatureCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page'     => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1'],
            'search'   => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'page.integer'     => '頁碼必須是整數',
            'page.min'         => '頁碼最小值為 :min',
            'per_page.integer' => '每頁筆數必須是整數',
            'per_page.min'     => '每頁筆數最少為 :min 筆',
            'search.string'    => '搜尋欄位必須是字串格式',
            'search.max'       => '搜尋欄位不能超過 :max 個字元',
        ];
    }
}

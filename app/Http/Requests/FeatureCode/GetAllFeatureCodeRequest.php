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
}

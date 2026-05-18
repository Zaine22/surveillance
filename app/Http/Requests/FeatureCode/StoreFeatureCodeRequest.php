<?php

namespace App\Http\Requests\FeatureCode;

use Illuminate\Foundation\Http\FormRequest;

class StoreFeatureCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'feature_code' => ['required', 'string', 'max:255'],
            'remark' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];
    }
}
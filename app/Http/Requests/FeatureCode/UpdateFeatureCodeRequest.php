<?php

namespace App\Http\Requests\FeatureCode;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFeatureCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'feature_code' => ['sometimes', 'required', 'string', 'max:255'],
            'remark' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.string'           => '標題必須是字串格式',
            'title.max'              => '標題不能超過 :max 個字元',
            'feature_code.required'  => '功能代碼不能為空',
            'feature_code.string'    => '功能代碼必須是字串格式',
            'feature_code.max'       => '功能代碼不能超過 :max 個字元',
            'remark.string'          => '備註必須是字串格式',
            'image.image'            => '檔案必須是圖片格式',
            'image.mimes'            => '圖片格式必須是以下其中之一：jpg、jpeg、png、webp',
            'image.max'              => '圖片大小不能超過 :max KB',
        ];
    }
}
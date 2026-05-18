<?php

namespace App\Http\Requests\SystemNotice;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreNoticeRequest extends FormRequest
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
            'title' => 'required|string|max:100',
            'content' => 'required|string',
            'status' => 'nullable|string|in:published,pending,removed',
            'publish_date' => 'required|date',
            'expire_at' => 'required|date|after:published_date',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'       => '標題不能為空',
            'title.string'         => '標題必須是字串格式',
            'title.max'            => '標題不能超過 :max 個字元',
            'content.required'     => '內容不能為空',
            'content.string'       => '內容必須是字串格式',
            'status.string'        => '狀態必須是字串格式',
            'status.in'            => '狀態必須是以下其中之一：已發布、待發布、已移除',
            'publish_date.required' => '發布日期不能為空',
            'publish_date.date'    => '發布日期必須是有效的日期格式',
            'expire_at.required'   => '到期日期不能為空',
            'expire_at.date'       => '到期日期必須是有效的日期格式',
            'expire_at.after'      => '到期日期必須在發布日期之後',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = response()->json(['errors' => $validator->errors()], 422);
        throw new HttpResponseException($response);
    }
}
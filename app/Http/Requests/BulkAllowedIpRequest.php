<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkAllowedIpRequest extends FormRequest
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
            'records' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $lines = preg_split('/\r\n|\r|\n/', $value);

                    foreach ($lines as $line) {
                        $line = trim($line);

                        if (! $line) {
                            continue;
                        }

                        $parts = explode(',', $line);

                        if (count($parts) !== 2) {
                            $fail('每一行的格式必須為：名稱, IP位址');
                            return;
                        }

                        $ip = trim($parts[1]);

                        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
                            $fail("「{$ip}」不是一個有效的 IP 位址。");
                            return;
                        }
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            // 'records.required' => 'IP records are required.',
            'records.required' => '需要 IP 記錄。',
            // 'records.string'   => 'Records must be text.',
            'records.string'   => '記錄必須為文字格式。',
        ];
    }
}

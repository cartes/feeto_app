<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'channel' => ['nullable', Rule::in(['manual', 'whatsapp', 'email', 'both'])],
        ];
    }

    public function messages(): array
    {
        return [
            'channel.in' => 'El canal de envío seleccionado no es válido.',
        ];
    }
}

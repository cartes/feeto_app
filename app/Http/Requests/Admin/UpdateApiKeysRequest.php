<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApiKeysRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_super_admin === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'gemini_api_key' => ['nullable', 'string', 'max:500'],
            'openai_api_key' => ['nullable', 'string', 'max:500'],
            'anthropic_api_key' => ['nullable', 'string', 'max:500'],
            'ai_provider' => ['nullable', 'string', 'in:gemini,openai,anthropic'],
            'ai_image_provider' => ['nullable', 'string', 'in:gemini,openai,anthropic'],
            'boostr_api_key' => ['nullable', 'string', 'max:500'],
            'boostr_base_url' => ['nullable', 'url', 'max:255'],
            'mp_access_token' => ['nullable', 'string', 'max:500'],
            'mp_public_key' => ['nullable', 'string', 'max:500'],
            'mp_webhook_secret' => ['nullable', 'string', 'max:500'],
            'mp_sandbox' => ['nullable', 'string', 'in:true,false'],
        ];
    }
}

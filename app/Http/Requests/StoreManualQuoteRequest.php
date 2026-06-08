<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Multitenancy\Models\Tenant;

class StoreManualQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tenantId = Tenant::current()?->id;

        return [
            'client_id' => [
                'required',
                'integer',
                Rule::exists('clients', 'id')->where('tenant_id', $tenantId),
            ],
            'vehicle_id' => [
                'required',
                'integer',
                Rule::exists('vehicles', 'id')->where('client_id', $this->input('client_id')),
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'client_id.required' => 'Debes seleccionar un cliente.',
            'client_id.exists' => 'El cliente seleccionado no es válido.',
            'vehicle_id.required' => 'Debes seleccionar un vehículo del cliente.',
            'vehicle_id.exists' => 'El vehículo seleccionado no pertenece al cliente indicado.',
        ];
    }
}

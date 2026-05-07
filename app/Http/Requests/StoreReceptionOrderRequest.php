<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Tenant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReceptionOrderRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'plate' => strtoupper((string) preg_replace('/[^A-Z0-9]/i', '', (string) $this->input('plate'))),
        ]);
    }

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
            'plate' => ['required', 'string', 'size:6', 'regex:/^[A-Z0-9]+$/'],
            'brand' => ['required', 'string', 'max:255'],
            'model' => ['required', 'string', 'max:255'],
            'client_name' => ['required', 'string', 'max:255'],
            'client_rut' => ['required', 'string', 'max:255'],
            'client_email' => ['nullable', 'email', 'max:255'],
            'client_phone' => ['nullable', 'string', 'max:255'],
            'selected_client_id' => [
                'nullable',
                'integer',
                Rule::exists('clients', 'id')->where(static function ($query) use ($tenantId): void {
                    if ($tenantId !== null) {
                        $query->where('tenant_id', $tenantId);
                    }
                }),
            ],
            'reassign_vehicle_owner' => ['required', 'boolean'],
            'appointment_id' => ['nullable', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'plate.required' => 'Debes ingresar una patente.',
            'plate.size' => 'La patente debe tener 6 caracteres.',
            'plate.regex' => 'La patente solo puede contener letras y números.',
            'brand.required' => 'Debes ingresar la marca del vehículo.',
            'model.required' => 'Debes ingresar el modelo del vehículo.',
            'client_name.required' => 'Debes ingresar el nombre del cliente.',
            'client_rut.required' => 'Debes ingresar el RUT del cliente.',
            'client_email.email' => 'El correo del cliente no es válido.',
            'selected_client_id.exists' => 'El cliente seleccionado no pertenece a este taller.',
            'reassign_vehicle_owner.required' => 'Debes indicar si quieres reasignar el dueño del vehículo.',
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Country;
use App\Models\Tenant;
use App\Rules\LicensePlate;
use App\Rules\ValidIdentification;
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
            'plate' => ['required', 'string', new LicensePlate(Tenant::current()?->country() ?? Country::Chile)],
            'vehicle_brand_id' => ['nullable', 'integer', Rule::exists('vehicle_brands', 'id')],
            'vehicle_model_id' => [
                'nullable',
                'integer',
                Rule::exists('vehicle_models', 'id')->where(function ($query): void {
                    $brandId = $this->integer('vehicle_brand_id');

                    if ($brandId > 0) {
                        $query->where('vehicle_brand_id', $brandId);
                    }
                }),
            ],
            'brand' => ['required', 'string', 'max:255'],
            'model' => ['required', 'string', 'max:255'],
            'client_name' => ['required', 'string', 'max:255'],
            'client_rut' => ['required', 'string', 'max:255', new ValidIdentification(Tenant::current()?->country() ?? Country::Chile)],
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
            'checklist' => ['nullable', 'array'],
            'checklist.fuel_level' => ['nullable', 'integer', 'between:0,100'],
            'checklist.damages' => ['nullable', 'array', 'max:50'],
            'checklist.damages.*.x' => ['required', 'numeric', 'between:0,100'],
            'checklist.damages.*.y' => ['required', 'numeric', 'between:0,100'],
            'checklist.damages.*.type' => [
                'required',
                'string',
                Rule::in(['rayon', 'abolladura', 'trizadura', 'pintura', 'falta_pieza', 'otro']),
            ],
            'checklist.damages.*.note' => ['nullable', 'string', 'max:255'],
            'checklist.belongings' => ['nullable', 'array', 'max:30'],
            'checklist.belongings.*' => ['required', 'string', 'max:255'],
            'checklist.notes' => ['nullable', 'string', 'max:2000'],
            'checklist.signature' => ['nullable', 'string', 'max:2000000', 'regex:#^data:image/png;base64,#'],
            'checklist.signed_by_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'plate.required' => 'Debes ingresar una patente.',
            'vehicle_brand_id.exists' => 'La marca seleccionada no es válida.',
            'vehicle_model_id.exists' => 'El modelo seleccionado no pertenece a la marca indicada.',
            'brand.required' => 'Debes ingresar la marca del vehículo.',
            'model.required' => 'Debes ingresar el modelo del vehículo.',
            'client_name.required' => 'Debes ingresar el nombre del cliente.',
            'client_rut.required' => 'Debes ingresar el RUT del cliente.',
            'client_email.email' => 'El correo del cliente no es válido.',
            'selected_client_id.exists' => 'El cliente seleccionado no pertenece a este taller.',
            'reassign_vehicle_owner.required' => 'Debes indicar si quieres reasignar el dueño del vehículo.',
            'checklist.damages.*.type.in' => 'El tipo de daño marcado no es válido.',
            'checklist.signature.regex' => 'La firma debe ser una imagen PNG válida.',
        ];
    }
}

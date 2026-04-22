<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreClientInvoiceRequest extends FormRequest
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
        return [
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'work_order_id' => ['nullable', 'integer', 'exists:work_orders,id'],
            'quote_id' => ['nullable', 'integer', 'exists:quotes,id'],
            'invoice_number' => ['nullable', 'string', 'max:255'],
            'amount_total' => ['required', 'numeric', 'min:1'],
            'amount_due' => ['nullable', 'numeric', 'min:0'],
            'issued_at' => ['required', 'date'],
            'due_at' => ['required', 'date', 'after_or_equal:issued_at'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'client_id.required' => 'Debes seleccionar un cliente para la factura.',
            'client_id.exists' => 'El cliente seleccionado no es válido.',
            'amount_total.required' => 'Debes ingresar el monto total de la factura.',
            'amount_total.min' => 'El monto total debe ser mayor a cero.',
            'amount_due.min' => 'El saldo pendiente no puede ser negativo.',
            'issued_at.required' => 'Debes indicar la fecha de emisión.',
            'due_at.required' => 'Debes indicar la fecha de vencimiento.',
            'due_at.after_or_equal' => 'La fecha de vencimiento no puede ser anterior a la emisión.',
        ];
    }
}

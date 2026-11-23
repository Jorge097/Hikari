<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $national = fn () => $this->input('delivery') === 'nacional';

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'delivery' => ['required', Rule::in(['local', 'nacional'])],
            'shipping_address' => ['nullable', 'required_if:delivery,nacional', 'string', 'max:500'],
            'shipping_city' => [Rule::requiredIf($national), 'nullable', 'string', 'max:100'],
            'shipping_state' => [Rule::requiredIf($national), 'nullable', 'string', 'max:100'],
            'shipping_postal_code' => [Rule::requiredIf($national), 'nullable', 'digits:5'],
            'required_date' => ['nullable', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.scent' => ['required', 'string', 'max:100'],
            'items.*.size' => ['nullable', 'string', 'max:100'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'email' => 'correo',
            'phone' => 'teléfono',
            'shipping_address' => 'dirección',
            'shipping_city' => 'ciudad',
            'shipping_state' => 'estado',
            'shipping_postal_code' => 'código postal',
            'required_date' => 'fecha requerida',
            'items' => 'carrito',
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Support\SatCatalogs;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvoiceRequestForm extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // Normaliza antes de validar
    protected function prepareForValidation(): void
    {
        $this->merge([
            'folio' => mb_strtoupper(trim((string) $this->input('folio'))),
            'rfc' => mb_strtoupper(preg_replace('/\s+/', '', (string) $this->input('rfc'))),
            'tax_name' => mb_strtoupper(trim((string) $this->input('tax_name'))),
        ]);
    }

    public function rules(): array
    {
        return self::fiscalRules() + [
            'folio' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255'],
        ];
    }

    // Reglas compartidas con el panel
    public static function fiscalRules(): array
    {
        return [
            'rfc' => ['required', 'string', 'regex:'.SatCatalogs::RFC_REGEX],
            'tax_name' => ['required', 'string', 'max:254'],
            'tax_regime' => ['required', Rule::in(array_keys(SatCatalogs::TAX_REGIMES))],
            'tax_zip_code' => ['required', 'digits:5'],
            'cfdi_use' => ['required', Rule::in(array_keys(SatCatalogs::CFDI_USES))],
        ];
    }

    public function attributes(): array
    {
        return [
            'rfc' => 'RFC',
            'tax_name' => 'nombre o razón social',
            'tax_regime' => 'régimen fiscal',
            'tax_zip_code' => 'código postal fiscal',
            'cfdi_use' => 'uso del CFDI',
            'email' => 'correo',
        ];
    }

    public function messages(): array
    {
        return [
            'rfc.regex' => 'El RFC no tiene un formato válido (12 o 13 caracteres, sin RFC genérico).',
        ];
    }
}

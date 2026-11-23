<?php

namespace App\Support;

use App\Enums\PaymentMethod;

// Catálogos del SAT (CFDI 4.0) usados en el sitio
class SatCatalogs
{
    public const TAX_REGIMES = [
        '601' => '601 - General de Ley Personas Morales',
        '603' => '603 - Personas Morales con Fines no Lucrativos',
        '605' => '605 - Sueldos y Salarios e Ingresos Asimilados a Salarios',
        '606' => '606 - Arrendamiento',
        '607' => '607 - Régimen de Enajenación o Adquisición de Bienes',
        '608' => '608 - Demás ingresos',
        '610' => '610 - Residentes en el Extranjero sin Establecimiento Permanente en México',
        '611' => '611 - Ingresos por Dividendos (socios y accionistas)',
        '612' => '612 - Personas Físicas con Actividades Empresariales y Profesionales',
        '614' => '614 - Ingresos por intereses',
        '615' => '615 - Régimen de los ingresos por obtención de premios',
        '616' => '616 - Sin obligaciones fiscales',
        '620' => '620 - Sociedades Cooperativas de Producción',
        '621' => '621 - Incorporación Fiscal',
        '622' => '622 - Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras',
        '623' => '623 - Opcional para Grupos de Sociedades',
        '624' => '624 - Coordinados',
        '625' => '625 - Actividades Empresariales con ingresos a través de Plataformas Tecnológicas',
        '626' => '626 - Régimen Simplificado de Confianza',
    ];

    public const CFDI_USES = [
        'G01' => 'G01 - Adquisición de mercancías',
        'G03' => 'G03 - Gastos en general',
        'S01' => 'S01 - Sin efectos fiscales',
        'CP01' => 'CP01 - Pagos',
    ];

    // Formas de pago SAT
    public const PAYMENT_FORMS = [
        '01' => '01 - Efectivo',
        '03' => '03 - Transferencia electrónica de fondos',
        '04' => '04 - Tarjeta de crédito',
        '28' => '28 - Tarjeta de débito',
        '99' => '99 - Por definir',
    ];

    // RFC de persona física (13) o moral (12); excluye genéricos
    public const RFC_REGEX = '/^(?!XAXX010101000$|XEXX010101000$)[A-ZÑ&]{3,4}\d{6}[A-Z\d]{3}$/u';

    public static function paymentForm(?PaymentMethod $method): string
    {
        return match ($method) {
            PaymentMethod::Efectivo, PaymentMethod::Oxxo => '01',
            PaymentMethod::Transferencia, PaymentMethod::Spei => '03',
            // Stripe no distingue crédito/débito aquí; se puede ajustar antes de timbrar
            PaymentMethod::Tarjeta => '04',
            default => '99',
        };
    }
}

<?php

return [

    // Tasa de IVA
    'tax_rate' => (float) env('HIKARI_TAX_RATE', 0.16),

    // true: los precios del catálogo ya incluyen IVA
    'prices_include_tax' => (bool) env('HIKARI_PRICES_INCLUDE_TAX', true),

    // Envío nacional en centavos (0 = sin cargo); pendiente de confirmar con Charlotte
    'shipping_national_cents' => (int) env('HIKARI_SHIPPING_NATIONAL_CENTS', 0),

    // Destinatarios del reporte mensual (separados por coma)
    'report_emails' => array_filter(array_map('trim', explode(',', (string) env('HIKARI_REPORT_EMAILS', '')))),

    // Folio: HK-001, HK-002... HK-1000
    'folio_prefix' => 'HK-',
    'folio_padding' => 3,

];

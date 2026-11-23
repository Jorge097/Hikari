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

    // Datos del responsable para el aviso de privacidad (completar en .env)
    'privacy' => [
        'version' => '2026-11-22',
        'responsible' => env('HIKARI_PRIVACY_RESPONSIBLE', 'Hikari Candles & Co.'),
        'address' => env('HIKARI_PRIVACY_ADDRESS'),
        'email' => env('HIKARI_PRIVACY_EMAIL'),
        'phone' => env('HIKARI_PRIVACY_PHONE', '871 781 1541'),
    ],

    // Folio: HK-001, HK-002... HK-1000
    'folio_prefix' => 'HK-',
    'folio_padding' => 3,

];

<?php

return [

    // Tasa de IVA
    'tax_rate' => (float) env('HIKARI_TAX_RATE', 0.16),

    // true: los precios del catálogo ya incluyen IVA
    'prices_include_tax' => (bool) env('HIKARI_PRICES_INCLUDE_TAX', true),

    // Folio: HK-001, HK-002... HK-1000
    'folio_prefix' => 'HK-',
    'folio_padding' => 3,

];

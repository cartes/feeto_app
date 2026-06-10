<?php

return [
    // IVA Chile aplicado a las comisiones de Mercado Pago.
    'vat_rate' => (float) env('BILLING_VAT_RATE', 0.19),

    // Margen mínimo sobre el costo del producto antes de emitir alerta.
    'minimum_margin_rate' => (float) env('BILLING_MINIMUM_MARGIN_RATE', 0.10),
];

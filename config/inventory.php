<?php

return [
    // Menos de esto = stock bajo
    'low_stock_threshold' => (int) env('INVENTORY_LOW_STOCK_THRESHOLD', 5),
];

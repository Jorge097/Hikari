<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlySalesReportItem extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'exchange_rate' => 'decimal:6',
            'subtotal_cents' => 'integer',
            'tax_cents' => 'integer',
            'shipping_cents' => 'integer',
            'total_cents' => 'integer',
            'total_mxn_cents' => 'integer',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(MonthlySalesReport::class, 'monthly_sales_report_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}

<?php

namespace App\Models;

use App\Services\Reports\SalesReport;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonthlySalesReport extends Model
{
    protected $fillable = [
        'year', 'month', 'orders_count',
        'subtotal_mxn_cents', 'tax_mxn_cents', 'shipping_mxn_cents', 'total_mxn_cents',
        'file_path', 'generated_at', 'emailed_at',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'orders_count' => 'integer',
            'subtotal_mxn_cents' => 'integer',
            'tax_mxn_cents' => 'integer',
            'shipping_mxn_cents' => 'integer',
            'total_mxn_cents' => 'integer',
            'generated_at' => 'datetime',
            'emailed_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(MonthlySalesReportItem::class);
    }

    // "Noviembre 2026"
    public function periodLabel(): string
    {
        return SalesReport::monthName($this->month).' '.$this->year;
    }

    public function fileName(): string
    {
        return sprintf('ventas-%04d-%02d.xlsx', $this->year, $this->month);
    }
}

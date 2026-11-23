<?php

namespace App\Services\Reports;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

// Cifras del dashboard; montos en centavos MXN, corte por fecha de pago
class DashboardMetrics
{
    public const PERIODS = [
        'month' => 'Este mes',
        '30d' => 'Últimos 30 días',
        '90d' => 'Últimos 90 días',
        'year' => 'Este año',
        '12m' => 'Últimos 12 meses',
    ];

    public readonly CarbonImmutable $from;

    public readonly CarbonImmutable $to;

    // 'day' o 'month' para las gráficas
    public readonly string $unit;

    public function __construct(?string $period = null)
    {
        $now = CarbonImmutable::now(config('app.timezone'));

        [$this->from, $this->unit] = match ($period) {
            '30d' => [$now->subDays(29)->startOfDay(), 'day'],
            '90d' => [$now->subDays(89)->startOfDay(), 'day'],
            'year' => [$now->startOfYear(), 'month'],
            '12m' => [$now->subMonthsNoOverflow(11)->startOfMonth(), 'month'],
            default => [$now->startOfMonth(), 'day'],
        };

        $this->to = $now->endOfDay();
    }

    /**
     * @return array{gross: int, refunds: int, fees: int, net: int, orders: int, average: int}
     */
    public function summary(): array
    {
        $orders = $this->paidOrders();
        $gross = (int) $orders->sum('mxn');
        $refunds = (int) $this->refunds()->sum('amount_cents');
        $fees = (int) $this->stripePayments()->sum('fee_cents');

        return [
            'gross' => $gross,
            'refunds' => $refunds,
            'fees' => $fees,
            'net' => $gross - $refunds - $fees,
            'orders' => $orders->count(),
            'average' => $orders->count() ? (int) round($gross / $orders->count()) : 0,
        ];
    }

    /**
     * Serie por día o por mes.
     *
     * @return array{labels: array<int, string>, gross: array<int, float>, net: array<int, float>, fees: array<int, float>}
     */
    public function series(): array
    {
        $format = $this->unit === 'day' ? 'Y-m-d' : 'Y-m';
        $buckets = collect(CarbonPeriod::create($this->from, "1 {$this->unit}", $this->to))
            ->mapWithKeys(fn ($date) => [$date->format($format) => 0]);

        $sum = fn (Collection $rows, string $amount) => $buckets->merge(
            $rows->groupBy(fn ($row) => $row->paid_at->format($format))->map->sum($amount)
        )->only($buckets->keys());

        $gross = $sum($this->paidOrders(), 'mxn');
        $refunds = $sum($this->refunds(), 'amount_cents');
        $fees = $sum($this->stripePayments(), 'fee_cents');

        $pesos = fn (Collection $values) => $values->map(fn ($cents) => round($cents / 100, 2))->values()->all();

        return [
            'labels' => $buckets->keys()->map(fn ($key) => $this->label($key))->all(),
            'gross' => $pesos($gross),
            'net' => $pesos($gross->map(fn ($value, $key) => $value - $refunds[$key] - $fees[$key])),
            'fees' => $pesos($fees),
        ];
    }

    /**
     * Velas más vendidas (piezas) con el resto agrupado.
     *
     * @return array<string, int>
     */
    public function topProducts(int $limit = 7): array
    {
        $totals = OrderItem::query()
            ->whereHas('order', fn ($query) => $query->whereBetween('paid_at', [$this->from, $this->to]))
            ->selectRaw('product_name, SUM(quantity) as pieces')
            ->groupBy('product_name')
            ->orderByDesc('pieces')
            ->pluck('pieces', 'product_name')
            ->map(fn ($pieces) => (int) $pieces);

        $top = $totals->take($limit);
        $rest = $totals->slice($limit)->sum();

        return $rest > 0 ? $top->put('Otras', $rest)->all() : $top->all();
    }

    // Pedidos pagados en el periodo con su total en MXN
    private function paidOrders(): Collection
    {
        return Order::query()
            ->whereBetween('paid_at', [$this->from, $this->to])
            ->get(['id', 'paid_at', 'total_cents', 'currency', 'exchange_rate'])
            ->each(fn (Order $order) => $order->mxn = (int) round(
                $order->total_cents * ($order->currency === 'MXN' ? 1 : (float) $order->exchange_rate)
            ));
    }

    private function refunds(): Collection
    {
        return $this->completedPayments()->where('type', PaymentType::Reembolso)->get(['paid_at', 'amount_cents']);
    }

    private function stripePayments(): Collection
    {
        return $this->completedPayments()
            ->where('type', PaymentType::Pago)
            ->where('provider', 'stripe')
            ->get(['paid_at', 'fee_cents']);
    }

    private function completedPayments()
    {
        return Payment::query()
            ->where('status', PaymentStatus::Completado)
            ->whereBetween('paid_at', [$this->from, $this->to]);
    }

    // "22 nov" o "nov 2026"
    private function label(string $key): string
    {
        $date = CarbonImmutable::parse(strlen($key) === 7 ? "{$key}-01" : $key)->locale('es');

        // Sin el punto de la abreviatura ("nov.")
        return str_replace('.', '', $this->unit === 'day' ? $date->isoFormat('D MMM') : $date->isoFormat('MMM YYYY'));
    }
}

<?php

namespace Database\Seeders;

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// 20 pedidos de prueba para ver el panel con datos. Solo local:
// php artisan db:seed --class=DemoSalesSeeder
class DemoSalesSeeder extends Seeder
{
    public const DOMAIN = 'demo.hikari.test';

    // [días atrás, estado final, cómo pagó, envío $, factura]
    private const ORDERS = [
        // Recién hechos, sin pagar
        [0, 'pendiente', null, 150, null],
        [1, 'pendiente', null, 0, null],
        [2, 'pendiente', null, 180, null],
        // Pagados, por enviar
        [1, 'pagado', 'stripe', 150, 'timbrada'],
        [2, 'pagado', 'transferencia', 0, null],
        [3, 'pagado', 'stripe', 180, 'solicitada'],
        [4, 'pagado', 'efectivo', 0, null],
        // En ruta
        [5, 'enviado', 'stripe', 150, null],
        [7, 'enviado', 'stripe', 180, 'timbrada'],
        [9, 'enviado', 'transferencia', 150, null],
        [11, 'enviado', 'stripe', 220, 'error'],
        // Entregados
        [16, 'entregado', 'stripe', 150, 'timbrada'],
        [24, 'entregado', 'efectivo', 0, null],
        [38, 'entregado', 'stripe', 180, null],
        [52, 'entregado', 'transferencia', 150, 'timbrada'],
        [71, 'entregado', 'stripe', 150, null],
        [95, 'entregado_parcial', 'stripe', 180, null], // con reembolso parcial
        // Reembolsados y cancelado
        [20, 'reembolsado', 'stripe', 150, null],
        [60, 'reembolsado', 'stripe', 0, null],
        [33, 'cancelado', null, 150, null],
    ];

    private const CUSTOMERS = [
        'Ana López', 'Mariana Pérez', 'Sofía Ramírez', 'Daniela Torres', 'Valeria Gómez',
        'Lucía Hernández', 'Fernanda Castro', 'Paola Ruiz', 'Andrea Morales', 'Gabriela Ortiz',
    ];

    private const CARRIERS = [
        ['Estafeta', 'https://www.estafeta.com/rastreo'],
        ['DHL', 'https://www.dhl.com/mx-es/home/rastreo.html'],
        ['FedEx', 'https://www.fedex.com/es-mx/tracking.html'],
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('DemoSalesSeeder no corre en producción.');

            return;
        }

        $products = Product::query()->where('price', '>', 0)->orderBy('id')->get();
        if ($products->isEmpty()) {
            $this->command?->error('No hay productos con precio.');

            return;
        }

        DB::transaction(function () use ($products) {
            $this->clean();

            $customers = collect(self::CUSTOMERS)->map(fn ($name, $i) => Customer::create([
                'name' => $name,
                'email' => Str::slug($name, '.').'@'.self::DOMAIN,
                'phone' => '8711'.str_pad((string) ($i * 7919 % 1000000), 6, '0', STR_PAD_LEFT),
                'rfc' => strtoupper(substr(Str::slug($name, ''), 0, 4)).'90010'.$i.'AB'.$i,
                'tax_name' => mb_strtoupper($name),
                'tax_regime' => '605',
                'tax_zip_code' => '27000',
                'cfdi_use' => 'G03',
            ]));

            foreach (self::ORDERS as $n => [$daysAgo, $status, $paidWith, $shipping, $invoice]) {
                $this->createOrder($n, $customers[$n % $customers->count()], $products, $daysAgo, $status, $paidWith, $shipping, $invoice);
            }
        });

        $this->command?->info('20 pedidos de prueba cargados (clientes @'.self::DOMAIN.').');
    }

    private function createOrder(int $n, Customer $customer, $products, int $daysAgo, string $status, ?string $paidWith, int $shipping, ?string $invoice): void
    {
        $created = CarbonImmutable::now()->subDays($daysAgo)->setTime(9 + $n % 10, ($n * 13) % 60);

        $order = $customer->orders()->create([
            'origin' => $paidWith === 'efectivo' ? 'whatsapp' : 'web',
            'shipping_cents' => $shipping * 100,
            'shipping_name' => $customer->name,
            'shipping_phone' => $customer->phone,
            'shipping_address' => 'Calle Morelos '.(100 + $n * 7),
            'shipping_city' => 'Torreón',
            'shipping_state' => 'Coahuila',
            'shipping_postal_code' => '27000',
            'privacy_accepted_at' => $created,
            'privacy_version' => '2026-11',
        ]);
        // Sin correo de confirmación para los de prueba
        $order->forceFill(['confirmation_sent_at' => $created])->saveQuietly();

        // 1 a 3 velas distintas
        foreach (range(0, $n % 3) as $k) {
            $product = $products[($n * 5 + $k * 11) % $products->count()];
            $order->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => 1 + ($n + $k) % 3,
                'unit_price_cents' => $product->unitPriceCents(null),
            ]);
        }
        $order->recalculateTotals();

        if ($status === 'cancelado') {
            $order->forceFill(['status' => OrderStatus::Cancelado, 'cancelled_at' => $created->addHours(5)])->save();
        }

        if ($paidWith) {
            $paidAt = $created->addMinutes(20);
            $stripe = $paidWith === 'stripe';

            $order->payments()->create([
                'provider' => $stripe ? 'stripe' : 'manual',
                'method' => $stripe ? 'tarjeta' : $paidWith,
                'status' => PaymentStatus::Completado,
                'amount_cents' => $order->total_cents,
                // Stripe México: 3.6% + $3 más IVA
                'fee_cents' => $stripe ? (int) round(($order->total_cents * 0.036 + 300) * 1.16) : null,
                'reference' => $stripe ? 'pi_demo_'.Str::lower(Str::random(14)) : 'DEMO-'.($n + 1),
                'paid_at' => $paidAt,
            ]);
            $order->refresh();

            if (in_array($status, ['enviado', 'entregado', 'entregado_parcial'], true)) {
                [$carrier, $url] = self::CARRIERS[$n % count(self::CARRIERS)];
                $order->forceFill([
                    'carrier' => $carrier,
                    'tracking_number' => (string) (7000000000 + $n * 104729),
                    'tracking_url' => $url,
                    'shipped_at' => $paidAt->addDay(),
                    'status' => OrderStatus::Enviado,
                ])->save();
            }

            if (in_array($status, ['entregado', 'entregado_parcial'], true)) {
                $order->forceFill(['delivered_at' => $paidAt->addDays(4), 'status' => OrderStatus::Entregado])->save();
            }

            if ($status === 'entregado_parcial' || $status === 'reembolsado') {
                $order->payments()->create([
                    'type' => PaymentType::Reembolso,
                    'provider' => 'stripe',
                    'method' => 'tarjeta',
                    'status' => PaymentStatus::Completado,
                    'amount_cents' => $status === 'reembolsado' ? $order->total_cents : (int) round($order->total_cents * 0.3),
                    'paid_at' => $paidAt->addDays(6),
                    'notes' => $status === 'reembolsado' ? 'Llegó rota' : 'Una vela con detalle',
                ]);
            }

            if ($invoice) {
                $this->createInvoice($order->refresh(), $customer, $invoice, $paidAt->addHours(3), $stripe);
            }
        }

        // Fechas como si se hubiera hecho ese día
        $order->forceFill(['created_at' => $created, 'updated_at' => $created])->saveQuietly();
    }

    private function createInvoice(Order $order, Customer $customer, string $status, CarbonImmutable $at, bool $card): void
    {
        $stamped = $status === 'timbrada';

        $order->invoices()->create([
            'status' => InvoiceStatus::from($status),
            'receiver_rfc' => $customer->rfc,
            'receiver_name' => $customer->tax_name,
            'receiver_tax_regime' => $customer->tax_regime,
            'receiver_zip_code' => $customer->tax_zip_code,
            'receiver_cfdi_use' => $customer->cfdi_use,
            'receiver_email' => $customer->email,
            'payment_form' => $card ? '04' : '03',
            'subtotal_cents' => $order->subtotal_cents,
            'tax_cents' => $order->tax_cents,
            'total_cents' => $order->total_cents,
            'provider' => 'facturama',
            'provider_id' => $stamped ? 'demo-'.Str::lower(Str::random(10)) : null,
            'uuid' => $stamped ? Str::upper((string) Str::uuid()) : null,
            'error_message' => $status === 'error' ? 'CFDI40147: El RFC del receptor no está en la lista de RFC inscritos (prueba).' : null,
            'stamped_at' => $stamped ? $at : null,
        ]);
    }

    // Borra lo que haya dejado una corrida anterior
    private function clean(): void
    {
        $customerIds = Customer::query()->where('email', 'like', '%@'.self::DOMAIN)->pluck('id');
        $orderIds = Order::query()->whereIn('customer_id', $customerIds)->pluck('id');

        DB::table('invoices')->whereIn('order_id', $orderIds)->update(['related_invoice_id' => null]);
        DB::table('invoices')->whereIn('order_id', $orderIds)->delete();
        Payment::query()->whereIn('order_id', $orderIds)->delete();
        DB::table('order_items')->whereIn('order_id', $orderIds)->delete();
        Order::query()->whereIn('id', $orderIds)->delete();
        Customer::query()->whereIn('id', $customerIds)->delete();

        // El folio sigue desde el último pedido real
        $last = Order::query()->pluck('folio')->map(fn ($folio) => (int) preg_replace('/\D/', '', $folio))->max() ?? 0;
        DB::table('sequences')->where('name', 'orders')->update(['value' => $last]);
    }
}

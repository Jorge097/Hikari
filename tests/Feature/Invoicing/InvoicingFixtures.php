<?php

namespace Tests\Feature\Invoicing;

use App\Models\Customer;
use App\Models\Order;
use App\Services\Invoicing\InvoiceProvider;
use Tests\Fakes\FakeInvoiceProvider;

trait InvoicingFixtures
{
    protected FakeInvoiceProvider $pac;

    protected function setUpInvoicing(): void
    {
        config(['hikari.invoicing.expedition_place' => '27000']);
        $this->pac = new FakeInvoiceProvider;
        $this->app->instance(InvoiceProvider::class, $this->pac);
    }

    // Pedido pagado: 2 × $290 + envío $150 = $730 IVA incluido
    protected function paidOrder(array $customer = []): Order
    {
        $order = Customer::factory()->create(['email' => 'ana@example.com'] + $customer)
            ->orders()->create(['shipping_cents' => 15000]);
        $order->items()->create(['product_name' => 'Vela pilar', 'scent' => 'Vainilla', 'quantity' => 2, 'unit_price_cents' => 29000]);
        $order->recalculateTotals();
        $order->payments()->create(['method' => 'tarjeta', 'status' => 'completado', 'amount_cents' => 73000]);

        return $order->refresh();
    }

    protected function fiscalData(array $overrides = []): array
    {
        return array_merge([
            'rfc' => 'LOPA800101AB1',
            'tax_name' => 'Ana López Pérez',
            'tax_regime' => '612',
            'tax_zip_code' => '27000',
            'cfdi_use' => 'G03',
        ], $overrides);
    }
}

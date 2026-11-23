<?php

namespace Tests\Feature\Invoicing;

use App\Enums\InvoiceStatus;
use App\Mail\InvoiceIssued;
use App\Services\Invoicing\CfdiBuilder;
use App\Services\Invoicing\InvoiceProviderException;
use App\Services\Invoicing\InvoiceService;
use App\Services\Reports\SalesReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    use InvoicingFixtures, RefreshDatabase;

    private InvoiceService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Storage::fake('local');
        $this->setUpInvoicing();
        $this->service = app(InvoiceService::class);
    }

    public function test_desglosa_iva_incluido_y_cuadra_al_centavo(): void
    {
        $order = $this->paidOrder();
        $lines = CfdiBuilder::lines($order);

        $this->assertCount(2, $lines);
        // $580 / 1.16 = $500 + $80 IVA; envío $150 / 1.16 = $129.31 + $20.69
        $this->assertSame([50000, 8000, 58000], [$lines[0]['subtotal'], $lines[0]['tax'], $lines[0]['total']]);
        $this->assertSame([12931, 2069, 15000], [$lines[1]['subtotal'], $lines[1]['tax'], $lines[1]['total']]);
        $this->assertSame($order->total_cents, CfdiBuilder::totals($lines)['total_cents']);
        $this->assertSame('78102203', $lines[1]['product_code']);
    }

    public function test_solicitud_guarda_receptor_y_datos_del_cliente(): void
    {
        $order = $this->paidOrder();

        $invoice = $this->service->request($order, $this->fiscalData());

        $this->assertSame(InvoiceStatus::Solicitada, $invoice->status);
        $this->assertSame('ANA LÓPEZ PÉREZ', $invoice->receiver_name);
        $this->assertSame('04', $invoice->payment_form);
        $this->assertSame(73000, $invoice->total_cents);
        $this->assertSame('ana@example.com', $invoice->receiver_email);
        $this->assertSame('LOPA800101AB1', $order->customer->refresh()->rfc);
    }

    public function test_no_factura_pedido_sin_pagar_ni_dos_veces(): void
    {
        $order = $this->paidOrder();
        $this->service->request($order, $this->fiscalData());

        $this->expectException(ValidationException::class);
        $this->service->request($order, $this->fiscalData());
    }

    public function test_no_factura_pedido_pendiente(): void
    {
        $order = $this->paidOrder();
        $order->payments()->delete();
        $order->syncPaymentStatus();

        $this->expectException(ValidationException::class);
        $this->service->request($order->refresh(), $this->fiscalData());
    }

    public function test_timbra_guarda_archivos_y_envia_correo(): void
    {
        $invoice = $this->service->request($this->paidOrder(), $this->fiscalData());

        $this->service->stamp($invoice);

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Timbrada, $invoice->status);
        $this->assertSame('00000000-0000-4000-8000-000000000001', $invoice->uuid);
        $this->assertSame('fake-1', $invoice->provider_id);
        Storage::disk('local')->assertExists($invoice->xml_path);
        Storage::disk('local')->assertExists($invoice->pdf_path);
        Mail::assertQueued(InvoiceIssued::class, fn ($mail) => $mail->hasTo('ana@example.com'));

        $cfdi = $this->pac->stamped[0];
        $this->assertSame('I', $cfdi['CfdiType']);
        $this->assertSame('27000', $cfdi['ExpeditionPlace']);
        $this->assertSame('LOPA800101AB1', $cfdi['Receiver']['Rfc']);
        $this->assertSame('612', $cfdi['Receiver']['FiscalRegime']);
        $this->assertEquals(500.0, $cfdi['Items'][0]['Subtotal']);
        $this->assertEquals(250.0, $cfdi['Items'][0]['UnitPrice']);
        $this->assertEquals(80.0, $cfdi['Items'][0]['Taxes'][0]['Total']);
    }

    public function test_rechazo_del_pac_queda_en_error_y_se_puede_reintentar(): void
    {
        $invoice = $this->service->request($this->paidOrder(), $this->fiscalData());
        $this->pac->failWith = 'El RFC del receptor no está en la lista de RFC inscritos.';

        try {
            $this->service->stamp($invoice);
            $this->fail('Debió fallar');
        } catch (InvoiceProviderException) {
        }

        $this->assertSame(InvoiceStatus::Error, $invoice->refresh()->status);
        $this->assertStringContainsString('RFC del receptor', $invoice->error_message);

        $this->pac->failWith = null;
        $this->service->stamp($invoice);
        $this->assertSame(InvoiceStatus::Timbrada, $invoice->refresh()->status);
        $this->assertNull($invoice->error_message);
    }

    public function test_sin_cp_de_expedicion_no_timbra(): void
    {
        config(['hikari.invoicing.expedition_place' => null]);
        $invoice = $this->service->request($this->paidOrder(), $this->fiscalData());

        $this->expectException(InvoiceProviderException::class);
        $this->service->stamp($invoice);
    }

    public function test_cancelacion_inmediata_o_pendiente(): void
    {
        $invoice = $this->service->stamp($this->service->request($this->paidOrder(), $this->fiscalData()));

        $this->pac->cancelStatus = 'pending';
        $this->service->cancel($invoice, '02');
        $this->assertSame(InvoiceStatus::CancelacionPendiente, $invoice->refresh()->status);

        $this->pac->cancelStatus = 'canceled';
        $this->service->cancel($invoice, '02');
        $this->assertSame(InvoiceStatus::Cancelada, $invoice->refresh()->status);
        $this->assertNotNull($invoice->cancelled_at);

        // Cancelada ya no bloquea una nueva factura
        $this->assertNotNull($this->service->request($invoice->order->refresh(), $this->fiscalData()));
    }

    public function test_nota_de_credito_relacionada_y_con_tope(): void
    {
        $invoice = $this->service->stamp($this->service->request($this->paidOrder(), $this->fiscalData()));

        $note = $this->service->creditNote($invoice, 29000);

        $this->assertSame('E', $note->type);
        $this->assertSame(InvoiceStatus::Timbrada, $note->status);
        $this->assertSame('G02', $note->receiver_cfdi_use);
        $cfdi = $this->pac->stamped[1];
        $this->assertSame('E', $cfdi['CfdiType']);
        $this->assertSame(['Type' => '01', 'Cfdis' => [['Uuid' => $invoice->uuid]]], $cfdi['Relations']);
        $this->assertEquals(290.0, $cfdi['Items'][0]['Total']);

        // Solo quedan $440 por acreditar
        $this->expectException(InvoiceProviderException::class);
        $this->service->creditNote($invoice, 44001);
    }

    public function test_reporte_incluye_rfc_y_uuid(): void
    {
        $invoice = $this->service->stamp($this->service->request($this->paidOrder(), $this->fiscalData()));

        $row = SalesReport::row($invoice->order->load('invoices', 'payments', 'customer'));

        $this->assertSame('LOPA800101AB1', $row['rfc']);
        $this->assertSame($invoice->uuid, $row['cfdi_uuid']);
    }
}

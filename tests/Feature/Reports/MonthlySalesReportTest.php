<?php

namespace Tests\Feature\Reports;

use App\Enums\OrderOrigin;
use App\Enums\PaymentStatus;
use App\Filament\Admin\Resources\MonthlySalesReports\Pages\ListMonthlySalesReports;
use App\Filament\Admin\Resources\MonthlySalesReports\Pages\ViewMonthlySalesReport;
use App\Filament\Admin\Resources\MonthlySalesReports\RelationManagers\ItemsRelationManager;
use App\Filament\Admin\Resources\Orders\Pages\ListOrders;
use App\Jobs\GenerateMonthlySalesReport;
use App\Mail\MonthlySalesReportMail;
use App\Models\Customer;
use App\Models\MonthlySalesReport;
use App\Models\Order;
use App\Models\User;
use App\Services\Reports\SalesReport;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use OpenSpout\Reader\XLSX\Reader;
use Tests\TestCase;

class MonthlySalesReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Mail::fake();
        config(['hikari.report_emails' => ['charlotte@hikari.test', 'contador@hikari.test']]);
    }

    // Pedido pagado en la fecha dada (hora de México)
    private function paidOrder(string $paidAt, int $priceCents = 10000, string $currency = 'MXN', ?float $rate = null): Order
    {
        $order = Customer::factory()->create()->orders()->create([
            'origin' => OrderOrigin::Whatsapp,
            'currency' => $currency,
            'exchange_rate' => $rate,
        ]);
        $order->items()->create(['product_name' => 'Vela', 'quantity' => 1, 'unit_price_cents' => $priceCents]);
        $order->recalculateTotals();
        $order->payments()->create([
            'method' => 'transferencia',
            'status' => PaymentStatus::Completado,
            'amount_cents' => $priceCents,
            'currency' => $currency,
            'paid_at' => Carbon::parse($paidAt, 'America/Mexico_City'),
        ]);

        return $order->refresh();
    }

    public function test_zona_horaria_de_la_app_es_mexico(): void
    {
        $this->assertSame('America/Mexico_City', config('app.timezone'));
    }

    public function test_corte_por_fecha_de_pago_en_hora_de_mexico(): void
    {
        $this->paidOrder('2026-10-31 23:30:00');   // octubre
        $nov1 = $this->paidOrder('2026-11-01 00:10:00');
        $nov30 = $this->paidOrder('2026-11-30 23:59:30');
        $this->paidOrder('2026-12-01 00:00:05');   // diciembre

        // Pendiente sin pago: no entra
        Customer::factory()->create()->orders()->create();

        [$from, $to] = SalesReport::monthRange(2026, 11);
        $folios = SalesReport::rows(SalesReport::paidBetween($from, $to))->pluck('folio')->all();

        $this->assertSame([$nov1->folio, $nov30->folio], $folios);
    }

    public function test_job_genera_snapshot_archivo_y_correo(): void
    {
        $this->paidOrder('2026-11-05 10:00:00', 29000);
        $this->paidOrder('2026-11-20 18:00:00', 10000, 'USD', 18.5);

        GenerateMonthlySalesReport::dispatchSync(2026, 11);

        $report = MonthlySalesReport::sole();
        $this->assertSame(2, $report->orders_count);
        // 290 MXN + 100 USD × 18.5
        $this->assertSame(29000 + 185000, $report->total_mxn_cents);
        $this->assertSame(2, $report->items()->count());
        $this->assertNotNull($report->emailed_at);

        Storage::disk('local')->assertExists('reports/ventas-2026-11.xlsx');

        // Lee el XLSX generado
        $reader = new Reader;
        $reader->open(Storage::disk('local')->path('reports/ventas-2026-11.xlsx'));
        $rows = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = $row->toArray();
            }
        }
        $reader->close();

        $this->assertSame(array_values(SalesReport::HEADERS), $rows[0]);
        $this->assertSame('HK-001', $rows[1][0]);
        $this->assertEquals(290, $rows[1][7]);
        $this->assertSame('USD', $rows[2][8]);
        $this->assertEquals(1850, $rows[2][10]);

        Mail::assertSent(MonthlySalesReportMail::class, function ($mail) {
            return $mail->hasTo('charlotte@hikari.test')
                && $mail->hasTo('contador@hikari.test')
                && $mail->hasAttachment(
                    Attachment::fromStorageDisk('local', 'reports/ventas-2026-11.xlsx')
                        ->as('ventas-2026-11.xlsx')
                        ->withMime('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                )
                && str_contains($mail->envelope()->subject, 'Aquí están las ventas de este mes');
        });
    }

    public function test_regenerar_reemplaza_sin_duplicar(): void
    {
        $this->paidOrder('2026-11-05 10:00:00');
        GenerateMonthlySalesReport::dispatchSync(2026, 11);

        // Pago OXXO tardío del mismo mes
        $this->paidOrder('2026-11-29 12:00:00');
        GenerateMonthlySalesReport::dispatchSync(2026, 11, sendEmail: false);

        $report = MonthlySalesReport::sole();
        $this->assertSame(2, $report->orders_count);
        $this->assertSame(2, $report->items()->count());
        Mail::assertSentCount(1);
    }

    public function test_sin_destinatarios_no_envia(): void
    {
        config(['hikari.report_emails' => []]);

        GenerateMonthlySalesReport::dispatchSync(2026, 11);

        Mail::assertNothingSent();
        $this->assertNull(MonthlySalesReport::sole()->emailed_at);
    }

    public function test_programado_ultimo_dia_a_las_2359_hora_de_mexico(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($e) => $e->description === 'cierre-mensual-ventas');

        $this->assertNotNull($event);
        $this->assertSame('America/Mexico_City', $event->timezone);
        $this->assertStringStartsWith('59 23 ', $event->expression);
    }

    public function test_comando_manual(): void
    {
        $this->paidOrder('2026-11-05 10:00:00');

        $this->artisan('reports:monthly', ['year' => 2026, 'month' => 11, '--no-email' => true])->assertSuccessful();

        $this->assertSame(1, MonthlySalesReport::sole()->orders_count);
        Mail::assertNothingSent();
    }

    public function test_panel_filtro_del_mes_exportacion_y_reportes(): void
    {
        config(['app.admin_emails' => ['admin@hikari.test']]);
        $this->actingAs(User::factory()->create(['email' => 'admin@hikari.test']));

        $nov = $this->paidOrder('2026-11-05 10:00:00');
        $oct = $this->paidOrder('2026-10-05 10:00:00');

        Livewire::test(ListOrders::class)
            ->filterTable('sales_month', ['month' => 11, 'year' => 2026])
            ->assertCanSeeTableRecords([$nov])
            ->assertCanNotSeeTableRecords([$oct])
            ->callAction('export_xlsx')
            ->assertFileDownloaded();

        Livewire::test(ListOrders::class)
            ->filterTable('paid_range', ['from' => '2026-10-01', 'until' => '2026-10-31'])
            ->assertCanSeeTableRecords([$oct])
            ->assertCanNotSeeTableRecords([$nov]);

        // Noviembre en curso: aún no se puede elegir
        $this->travelTo(now()->setDate(2026, 11, 20));
        Livewire::test(ListMonthlySalesReports::class)
            ->callAction('open_month', ['period' => '2026-11'])
            ->assertHasActionErrors(['period']);

        // Ya en diciembre: noviembre cerró y se genera al elegirlo
        $this->travelTo(now()->setDate(2026, 12, 5));
        Livewire::test(ListMonthlySalesReports::class)
            ->mountAction('open_month')
            ->assertActionDataSet(['period' => '2026-11']);

        Livewire::test(ListMonthlySalesReports::class)
            ->callAction('open_month', ['period' => '2026-11'])
            ->assertHasNoActionErrors()
            ->assertRedirect();

        $report = MonthlySalesReport::sole();
        Livewire::test(ListMonthlySalesReports::class)
            ->assertCanSeeTableRecords([$report])
            ->assertSee('Noviembre')
            ->filterTable('period', '2026-10')
            ->assertCanNotSeeTableRecords([$report]);
        $this->get('/panel_hikari/monthly-sales-reports')->assertOk()->assertSee('Noviembre 2026');
        $this->get("/panel_hikari/monthly-sales-reports/{$report->id}")->assertOk();

        // Gestor de relaciones (carga diferida)
        Livewire::test(ItemsRelationManager::class, ['ownerRecord' => $report, 'pageClass' => ViewMonthlySalesReport::class])
            ->assertCanSeeTableRecords($report->items)
            ->assertSee($nov->folio);
    }
}

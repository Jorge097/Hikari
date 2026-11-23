<?php

namespace App\Filament\Admin\Resources\Orders\Pages;

use App\Filament\Admin\Resources\Invoices\InvoiceResource;
use App\Filament\Admin\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Services\Invoicing\InvoiceService;
use App\Support\SatCatalogs;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\On;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected ?bool $hasDatabaseTransactions = true;

    public function getTitle(): string
    {
        return 'Pedido '.$this->record->folio;
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->invoiceAction(),
        ];
    }

    // Crea la solicitud de factura con los datos fiscales del cliente
    private function invoiceAction(): Action
    {
        return Action::make('invoice')
            ->label('Facturar')
            ->icon(Heroicon::OutlinedDocumentText)
            ->visible(fn (Order $record) => ! $record->activeInvoice())
            ->fillForm(fn (Order $record) => [
                'rfc' => $record->customer->rfc,
                'tax_name' => $record->customer->tax_name,
                'tax_regime' => $record->customer->tax_regime,
                'tax_zip_code' => $record->customer->tax_zip_code,
                'cfdi_use' => $record->customer->cfdi_use ?? 'G03',
                'email' => $record->customer->email,
            ])
            ->schema([
                TextInput::make('rfc')->label('RFC')->required()->regex(SatCatalogs::RFC_REGEX),
                TextInput::make('tax_name')->label('Nombre o razón social')->required()->maxLength(254),
                Select::make('tax_regime')->label('Régimen fiscal')->options(SatCatalogs::TAX_REGIMES)->required(),
                TextInput::make('tax_zip_code')->label('CP fiscal')->required()->length(5),
                Select::make('cfdi_use')->label('Uso del CFDI')->options(SatCatalogs::CFDI_USES)->required(),
                TextInput::make('email')->label('Enviar a')->email(),
            ])
            ->modalDescription('Queda como solicitud; revísala y timbra desde Facturas.')
            ->action(function (Order $record, array $data) {
                $invoice = app(InvoiceService::class)->request($record, $data);

                $this->redirect(InvoiceResource::getUrl('view', ['record' => $invoice]));
            });
    }

    // Pagos cambiaron: refresca estado y fecha de pago
    #[On('refresh-order')]
    public function refreshPaymentState(): void
    {
        $this->record->refresh();
        $this->refreshFormData(['status', 'paid_at']);
    }

    protected function afterSave(): void
    {
        /** @var Order $order */
        $order = $this->record;
        $order->recalculateTotals();
        $order->syncPaymentStatus();
    }
}

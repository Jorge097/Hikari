<?php

namespace App\Filament\Admin\Resources\Invoices\Pages;

use App\Filament\Admin\Resources\Invoices\InvoiceResource;
use Filament\Resources\Pages\EditRecord;

// Corrección de datos antes de timbrar
class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    public function getTitle(): string
    {
        return "Corregir factura del pedido {$this->record->order->folio}";
    }

    protected function getHeaderActions(): array
    {
        return [
            InvoiceResource::stampAction(),
        ];
    }
}

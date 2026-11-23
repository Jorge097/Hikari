<?php

namespace App\Filament\Admin\Resources\Invoices\Pages;

use App\Filament\Admin\Resources\Invoices\InvoiceResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    public function getTitle(): string
    {
        return "{$this->record->typeLabel()} del pedido {$this->record->order->folio}";
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            InvoiceResource::stampAction(),
            InvoiceResource::downloadAction('xml'),
            InvoiceResource::downloadAction('pdf'),
            InvoiceResource::fetchFilesAction(),
            InvoiceResource::creditNoteAction(),
            InvoiceResource::cancelAction(),
        ];
    }
}

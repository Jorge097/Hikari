<?php

namespace App\Filament\Admin\Resources\Orders\Pages;

use App\Filament\Admin\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Resources\Pages\EditRecord;
use Livewire\Attributes\On;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected ?bool $hasDatabaseTransactions = true;

    public function getTitle(): string
    {
        return 'Pedido '.$this->record->folio;
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

<?php

namespace App\Filament\Admin\Resources\Orders\Pages;

use App\Filament\Admin\Resources\Orders\OrderResource;
use App\Services\Reports\SalesReport;
use App\Services\Reports\SpreadsheetWriter;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                $this->exportAction('xlsx', 'Excel (XLSX)'),
                $this->exportAction('csv', 'CSV'),
            ])
                ->label('Exportar')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->button()
                ->color('gray'),
            CreateAction::make()->label('Registrar pedido'),
        ];
    }

    // Exporta exactamente lo filtrado en la tabla
    private function exportAction(string $format, string $label): Action
    {
        return Action::make("export_{$format}")
            ->label($label)
            ->action(function () use ($format): BinaryFileResponse {
                $rows = SalesReport::rows($this->getFilteredTableQuery());
                $path = sys_get_temp_dir().'/pedidos-'.Str::uuid().".{$format}";

                SpreadsheetWriter::write($rows, $path, $format);

                return response()
                    ->download($path, 'pedidos-'.now()->format('Y-m-d-His').".{$format}")
                    ->deleteFileAfterSend();
            });
    }
}

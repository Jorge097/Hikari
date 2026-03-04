<?php

namespace App\Filament\Admin\Resources\Scents\Pages;

use App\Filament\Admin\Resources\Scents\ScentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListScents extends ListRecords
{
    protected static string $resource = ScentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

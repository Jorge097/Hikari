<?php

namespace App\Filament\Admin\Resources\Scents\Pages;

use App\Filament\Admin\Resources\Scents\ScentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditScent extends EditRecord
{
    protected static string $resource = ScentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

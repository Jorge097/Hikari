<?php

namespace App\Filament\Admin\Support;

use Filament\Forms\Components\TextInput;

class MoneyInput
{
    // Se captura en pesos, se guarda en centavos
    public static function make(string $name): TextInput
    {
        return TextInput::make($name)
            ->numeric()
            ->minValue(0)
            ->step(0.01)
            ->prefix('$')
            ->formatStateUsing(fn ($state) => $state === null ? null : $state / 100)
            ->dehydrateStateUsing(fn ($state) => (int) round(((float) $state) * 100));
    }
}

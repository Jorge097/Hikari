<?php

namespace App\Filament\Admin\Resources\Customers\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(self::fields());
    }

    // Campos reutilizables (también al crear cliente desde un pedido)
    public static function fields(): array
    {
        return [
            TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->maxLength(255),
            TextInput::make('phone')
                ->label('Teléfono')
                ->tel()
                ->maxLength(30),
            TextInput::make('email')
                ->label('Correo')
                ->email()
                ->maxLength(255),
            Textarea::make('notes')
                ->label('Notas')
                ->columnSpanFull(),
        ];
    }
}

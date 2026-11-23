<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasColor, HasLabel
{
    case Admin = 'admin';
    case Contador = 'contador';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Contador => 'Contador (solo lectura)',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Admin => 'primary',
            self::Contador => 'info',
        };
    }
}

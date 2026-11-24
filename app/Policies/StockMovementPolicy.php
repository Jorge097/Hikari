<?php

namespace App\Policies;

use App\Models\User;

// Historial: se consulta, no se edita
class StockMovementPolicy extends AdminOnlyPolicy
{
    protected function write(User $user): bool
    {
        return false;
    }
}

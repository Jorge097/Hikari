<?php

namespace App\Policies;

use App\Models\User;

// Ventas: el contador consulta, solo el admin modifica
abstract class SalesPolicy extends AdminOnlyPolicy
{
    protected function read(User $user): bool
    {
        return $user->isAdmin() || $user->isContador();
    }
}

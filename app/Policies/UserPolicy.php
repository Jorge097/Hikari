<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy extends AdminOnlyPolicy
{
    // Nadie se borra a sí mismo
    public function delete(User $user, $record): bool
    {
        return $user->isAdmin() && ! $user->is($record);
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}

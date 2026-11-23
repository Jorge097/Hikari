<?php

namespace App\Policies;

use App\Models\User;

// Base: todo solo para administradores. Filament permite los métodos que falten, por eso van todos.
abstract class AdminOnlyPolicy
{
    protected function read(User $user): bool
    {
        return $user->isAdmin();
    }

    protected function write(User $user): bool
    {
        return $user->isAdmin();
    }

    public function viewAny(User $user): bool
    {
        return $this->read($user);
    }

    public function view(User $user, $record): bool
    {
        return $this->read($user);
    }

    public function create(User $user): bool
    {
        return $this->write($user);
    }

    public function update(User $user, $record): bool
    {
        return $this->write($user);
    }

    public function delete(User $user, $record): bool
    {
        return $this->write($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->write($user);
    }

    public function restore(User $user, $record): bool
    {
        return $this->write($user);
    }

    public function restoreAny(User $user): bool
    {
        return $this->write($user);
    }

    public function forceDelete(User $user, $record): bool
    {
        return $this->write($user);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->write($user);
    }

    public function replicate(User $user, $record): bool
    {
        return $this->write($user);
    }

    public function reorder(User $user): bool
    {
        return $this->write($user);
    }

    public function associate(User $user): bool
    {
        return $this->write($user);
    }

    public function associateAny(User $user): bool
    {
        return $this->write($user);
    }

    public function dissociate(User $user, $record): bool
    {
        return $this->write($user);
    }

    public function dissociateAny(User $user): bool
    {
        return $this->write($user);
    }

    public function attach(User $user): bool
    {
        return $this->write($user);
    }

    public function attachAny(User $user): bool
    {
        return $this->write($user);
    }

    public function detach(User $user, $record): bool
    {
        return $this->write($user);
    }

    public function detachAny(User $user): bool
    {
        return $this->write($user);
    }
}

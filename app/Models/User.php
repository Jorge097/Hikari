<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    // Admin por rol o por ADMIN_EMAILS (acceso de respaldo)
    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin
            || in_array(strtolower($this->email), config('app.admin_emails'), true);
    }

    public function isContador(): bool
    {
        return $this->role === UserRole::Contador;
    }

    // Usuarios sin rol (p. ej. registro público) no entran
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isAdmin() || $this->isContador();
    }
}

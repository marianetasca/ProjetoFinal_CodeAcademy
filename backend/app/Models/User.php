<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;   // permite criar tokens de acesso (Sanctum)

// Campos que podem ser preenchidos em massa (create/update com array).
// 'role' fica de fora de propósito: senão alguém poderia se registrar
// enviando "role": "admin" e virar administrador.
#[Fillable(['name', 'email', 'password'])]
// Campos que nunca aparecem quando o usuário é convertido para JSON.
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',   // a senha é criptografada automaticamente ao gravar
        ];
    }

    // Atalho usado nas verificações de permissão (middleware de admin).
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function orders(): HasMany    { return $this->hasMany(Order::class); }
    public function addresses(): HasMany { return $this->hasMany(Address::class); }
    public function cart(): HasOne       { return $this->hasOne(Cart::class); }
}

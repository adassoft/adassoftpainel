<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'usuario';
    public $timestamps = false;

    protected static function booted()
    {
        static::creating(function ($user) {
            // Garante campos legados obrigatórios
            $user->login = $user->login ?? $user->email;
            $user->data = $user->data ?? now();
            $user->status = $user->status ?? 'Ativo';
            $user->acesso = $user->acesso ?? 1; // Admin por padrão se via CLI
            $user->uf = $user->uf ?? 'SP'; // Default seguro
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nome',
        'name', // Virtual para Filament/Laravel
        'login',
        'email',
        'senha',
        'password', // Virtual para Filament/Laravel
        'acesso',
        'status',
        'cnpj',
        'foto',
        'uf',
        'data',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'senha',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'datetime',
            // 'senha' => 'hashed', // Removido para permitir controle manual e evitar conflitos
        ];
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPasswordName()
    {
        return 'senha';
    }

    // Compatibility for 'name' -> 'nome'
    public function getNameAttribute()
    {
        return $this->nome ?? $this->login;
    }

    public function setNameAttribute($value)
    {
        $this->attributes['nome'] = $value;
    }

    // Compatibility for setting 'password' -> 'senha'
    public function setPasswordAttribute($value)
    {
        $this->attributes['senha'] = $value;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // Admin geral pode acessar qualquer painel
        if ($this->email === 'admin@adassoft.com' || strtolower($this->acesso ?? '') === 'admin' || $this->acesso == 1) {
            return true;
        }

        // Usuário comum só acessa o painel 'app'
        if ($panel->getId() === 'app') {
            return true;
        }

        // Revendedor acessa o painel 'reseller' (Acesso 2)
        if ($panel->getId() === 'reseller' && (int) $this->acesso === 2) {
            return true;
        }

        return false;
    }

    public function empresa()
    {
        return $this->hasOne(Empresa::class, 'cnpj', 'cnpj');
    }

}

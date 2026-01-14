<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

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
        'name',
        'login',
        'email',
        'senha',
        'password',
        'acesso',
        'status',
        'cnpj', // Legacy field - Kept for compatibility
        'asaas_customer_id', // Novo Asaas ID
        'empresa_id', // Novo campo FK
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
        ];
    }

    public function getAuthPasswordName()
    {
        return 'senha';
    }

    public function getNameAttribute()
    {
        return $this->nome ?? $this->login;
    }

    public function setNameAttribute($value)
    {
        $this->attributes['nome'] = $value;
    }

    public function setPasswordAttribute($value)
    {
        $this->attributes['senha'] = $value;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if (strtolower($this->status) !== 'ativo') {
            return false;
        }

        if ($this->email === 'admin@adassoft.com' || strtolower($this->acesso ?? '') === 'admin' || $this->acesso == 1) {
            return true;
        }

        if ($panel->getId() === 'app') {
            return true;
        }

        if ($panel->getId() === 'reseller' && (int) $this->acesso === 2) {
            return true;
        }

        return false;
    }

    public function empresa()
    {
        // Agora usamos chave estrangeira real (empresa_id) apontando para a PK da empresa (codigo)
        return $this->belongsTo(Company::class, 'empresa_id', 'codigo');
    }

    public function library()
    {
        return $this->belongsToMany(Download::class, 'user_library', 'user_id', 'download_id')
            ->withPivot('order_id')
            ->withTimestamps();
    }

}

<?php

namespace App\Models;

use App\Enums\RoleEnum;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'role_id',
        'name',
        'email',
        'password',
        'avatar_path',
        'status',
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
            'status' => UserStatus::class,
        ];
    }

    /**
     * Perfil global do usuário.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Projetos em que o usuário atua como responsável/proprietário.
     */
    public function ownedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'owner_id');
    }

    /**
     * Projetos dos quais o usuário é membro de equipe.
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Verifica se o usuário possui perfil de Administrador.
     */
    public function isAdmin(): bool
    {
        return $this->role?->slug === RoleEnum::ADMIN;
    }

    /**
     * Verifica se o usuário possui perfil de Gerente.
     */
    public function isManager(): bool
    {
        return $this->role?->slug === RoleEnum::MANAGER;
    }

    /**
     * Verifica se o usuário possui perfil comum.
     */
    public function isUser(): bool
    {
        return $this->role?->slug === RoleEnum::USER;
    }

    /**
     * Verifica se a conta está ativa para autenticação e operações.
     */
    public function isActive(): bool
    {
        return $this->status === UserStatus::ACTIVE;
    }
}

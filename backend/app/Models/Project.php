<?php

namespace App\Models;

use App\Enums\ProjectRole;
use App\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'owner_id',
        'name',
        'code',
        'description',
        'status',
        'start_date',
        'due_date',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'start_date' => 'date:Y-m-d',
            'due_date' => 'date:Y-m-d',
        ];
    }

    /**
     * Proprietário / Responsável pelo projeto.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Membros da equipe vinculados ao projeto via tabela pivô.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Registros de associação direta da equipe.
     */
    public function memberRecords(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    /**
     * Verifica se o usuário informado é membro da equipe deste projeto.
     */
    public function hasMember(User $user): bool
    {
        if ($this->owner_id === $user->id) {
            return true;
        }

        return $this->memberRecords()->where('user_id', $user->id)->exists();
    }

    /**
     * Retorna a função do usuário neste projeto.
     */
    public function getUserRole(User $user): ?ProjectRole
    {
        if ($this->owner_id === $user->id) {
            return ProjectRole::OWNER;
        }

        /** @var ProjectMember|null $member */
        $member = $this->memberRecords()->where('user_id', $user->id)->first();

        return $member?->role;
    }

    /**
     * Verifica se o usuário é OWNER do projeto.
     */
    public function isOwner(User $user): bool
    {
        return $this->getUserRole($user) === ProjectRole::OWNER;
    }

    /**
     * Verifica se o usuário é OWNER ou MANAGER do projeto.
     */
    public function canManage(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $role = $this->getUserRole($user);

        return in_array($role, [ProjectRole::OWNER, ProjectRole::MANAGER], true);
    }

    /**
     * Tarefas associadas a este projeto.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}

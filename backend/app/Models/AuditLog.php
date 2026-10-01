<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'auditable_type',
        'auditable_id',
        'event',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Usuário que disparou a ação auditada.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Entidade polimórfica auditada (ex: Task, Project, User).
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeFilterEvent(Builder $query, ?string $event): Builder
    {
        if (! $event) {
            return $query;
        }

        return $query->where('event', $event);
    }

    public function scopeFilterAuditable(Builder $query, ?string $type, ?int $id = null): Builder
    {
        if (! $type) {
            return $query;
        }

        $query->where('auditable_type', $type);

        if ($id) {
            $query->where('auditable_id', $id);
        }

        return $query;
    }

    public function scopeFilterUser(Builder $query, ?int $userId): Builder
    {
        if (! $userId) {
            return $query;
        }

        return $query->where('user_id', $userId);
    }
}

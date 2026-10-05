<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'task_id',
        'user_id',
        'content',
    ];

    /**
     * Tarefa relacionada ao comentário.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Usuário autor do comentário.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Acessor para compatibilidade com atributo 'body'.
     */
    public function getBodyAttribute(): ?string
    {
        return $this->content;
    }

    /**
     * Mutator para compatibilidade com atributo 'body'.
     */
    public function setBodyAttribute(?string $value): void
    {
        $this->attributes['content'] = $value;
    }
}

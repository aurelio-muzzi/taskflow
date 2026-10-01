<?php

namespace App\Http\Resources\V1;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Task
 */
class TaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'project' => new ProjectResource($this->whenLoaded('project')),
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'status_label' => method_exists($this->status, 'label') ? $this->status->label() : (string) $this->status,
            'priority' => $this->priority instanceof \BackedEnum ? $this->priority->value : $this->priority,
            'priority_label' => method_exists($this->priority, 'label') ? $this->priority->label() : (string) $this->priority,
            'assigned_to' => $this->assigned_to,
            'assignee' => new UserResource($this->whenLoaded('assignee')),
            'created_by' => $this->created_by,
            'creator' => new UserResource($this->whenLoaded('creator')),
            'due_date' => $this->due_date?->format('Y-m-d'),
            'estimated_hours' => $this->estimated_hours !== null ? (float) $this->estimated_hours : null,
            'order' => $this->order,
            'comments_count' => (int) ($this->comments_count ?? $this->comments()->count()),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

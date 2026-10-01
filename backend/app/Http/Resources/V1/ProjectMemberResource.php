<?php

namespace App\Http\Resources\V1;

use App\Models\ProjectMember;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProjectMember
 */
class ProjectMemberResource extends JsonResource
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
            'user_id' => $this->user_id,
            'role' => $this->role instanceof \BackedEnum ? $this->role->value : $this->role,
            'role_label' => method_exists($this->role, 'label') ? $this->role->label() : (string) $this->role,
            'user' => new UserResource($this->whenLoaded('user')),
            'joined_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

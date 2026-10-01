<?php

namespace App\Http\Resources\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'avatar_url' => $this->avatar_path ? asset('storage/'.$this->avatar_path) : null,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'role' => new RoleResource($this->whenLoaded('role')),
            'is_admin' => $this->isAdmin(),
            'is_manager' => $this->isManager(),
            'is_user' => $this->isUser(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

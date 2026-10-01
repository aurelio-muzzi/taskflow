<?php

namespace App\Policies;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Determine whether the user can view any projects.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the project.
     */
    public function view(User $user, Project $project): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $project->hasMember($user);
    }

    /**
     * Determine whether the user can create projects.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can update the project.
     */
    public function update(User $user, Project $project): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $role = $project->getUserRole($user);

        return in_array($role, [ProjectRole::OWNER, ProjectRole::MANAGER], true);
    }

    /**
     * Determine whether the user can delete the project.
     */
    public function delete(User $user, Project $project): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $project->owner_id === $user->id;
    }

    /**
     * Determine whether the user can manage project members (add, update role, remove).
     */
    public function manageMembers(User $user, Project $project): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $role = $project->getUserRole($user);

        return in_array($role, [ProjectRole::OWNER, ProjectRole::MANAGER], true);
    }
}

<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

/**
 * Admins and the leader of the client's team manage every project of the client (ClientPolicy::assignProjects).
 * The member who brought the client works on all of its projects (ClientPolicy::update); other members only on the
 * projects the leader assigned them to as helpers. The owner can withdraw a project while it is still pending.
 * Invoices follow their project.
 */
class ProjectPolicy
{
    public function view(User $user, Project $project): bool
    {
        return $user->can('update', $project->client)
            || $project->assignees()->whereKey($user->id)->exists();
    }

    public function update(User $user, Project $project): bool
    {
        return $this->view($user, $project);
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->can('assignProjects', $project->client)
            || (! $project->isApproved() && $user->can('update', $project->client));
    }
}

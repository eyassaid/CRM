<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

/**
 * Admins and the team's leader see and manage all of a team's clients. A member owns the clients they brought
 * (created_by) and manages them and all their projects; they also see the clients of projects they help on.
 * Which team a new client may be assigned to is checked in the client Form Requests. Projects: see ProjectPolicy.
 */
class ClientPolicy
{
    public function view(User $user, Client $client): bool
    {
        if ($this->assignProjects($user, $client)) {
            return true;
        }

        return $user->belongsToTeam($client->team_id)
            && ($client->created_by === $user->id
                || $client->projects()->whereHas('assignees', fn ($query) => $query->whereKey($user->id))->exists());
    }

    public function update(User $user, Client $client): bool
    {
        return $this->assignProjects($user, $client)
            || ($user->belongsToTeam($client->team_id) && $client->created_by === $user->id);
    }

    /**
     * Choosing who works on the client's projects, and approving the projects members request, is for admins
     * and the team's leader.
     */
    public function assignProjects(User $user, Client $client): bool
    {
        return $user->isAdmin() || ($client->team !== null && $client->team->leader_id === $user->id);
    }

    public function delete(User $user, Client $client): bool
    {
        return $this->update($user, $client);
    }
}

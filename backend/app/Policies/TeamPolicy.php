<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

/**
 * Creating, listing, editing and deleting teams is admin-only (`admin` middleware).
 */
class TeamPolicy
{
    /**
     * The team's people see it (the project page uses it to list who can be assigned).
     */
    public function view(User $user, Team $team): bool
    {
        return $user->isAdmin() || $user->belongsToTeam($team->id);
    }

    /**
     * The per-member report is for the team leader and admins.
     */
    public function viewReport(User $user, Team $team): bool
    {
        return $user->isAdmin() || $team->leader_id === $user->id;
    }
}

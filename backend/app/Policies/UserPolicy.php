<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Admins list every user; team leaders list the members of the teams they lead.
     */
    public function viewAny(User $user): bool
    {
        return $this->isAdminOrLeader($user);
    }

    /**
     * Admins add employees and team leaders; team leaders add employees to the teams they lead (see UserRequest).
     */
    public function create(User $user): bool
    {
        return $this->isAdminOrLeader($user);
    }

    public function view(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    private function isAdminOrLeader(User $user): bool
    {
        return $user->isAdmin() || $user->isTeamLeader();
    }
}

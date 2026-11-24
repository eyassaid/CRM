<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Passport\HasApiTokens;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * Only team leaders can lead teams; they add employees to the teams they lead.
     */
    public function isTeamLeader(): bool
    {
        return $this->role === UserRole::TeamLeader;
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_user');
    }

    public function ledTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'leader_id');
    }

    /**
     * Clients this user brought.
     */
    public function clients(): HasMany
    {
        return $this->hasMany(Client::class, 'created_by');
    }

    /**
     * Ids of the teams the user is a member or the leader of.
     *
     * @return Collection<int, int>
     */
    public function teamIds(): Collection
    {
        return $this->teams()->pluck('teams.id')
            ->merge($this->ledTeams()->pluck('id'))
            ->unique()
            ->values();
    }

    public function belongsToTeam(?int $teamId): bool
    {
        return $teamId !== null && $this->teamIds()->contains($teamId);
    }
}

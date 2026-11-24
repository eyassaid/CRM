<?php

namespace App\Models;

use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory;

    protected $fillable = ['name', 'phone_number', 'image', 'team_id', 'created_by'];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Admins see every client and team leaders their team's clients. Other members of the team see the clients
     * they brought and the clients of the projects they are assigned to (same rule as ClientPolicy::view).
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->isAdmin()) {
            return;
        }

        $query->whereIn('team_id', $user->teamIds())
            ->where(fn (Builder $query) => $query
                ->whereHas('team', fn (Builder $team) => $team->where('leader_id', $user->id))
                ->orWhere('created_by', $user->id)
                ->orWhereHas('projects.assignees', fn (Builder $assignee) => $assignee->whereKey($user->id)));
    }
}

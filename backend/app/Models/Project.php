<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    protected $fillable = ['name', 'status', 'client_id', 'image', 'approved_at'];

    // Matches the column default, so a new model has a status before it is refreshed.
    protected $attributes = ['status' => 'not_started'];

    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * The people working on the project (leader or members of the client's team).
     */
    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user')->withTimestamps();
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Projects requested by an employee are pending until the team leader or an admin approves them.
     */
    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }

    /**
     * Admins see every project, team leaders the projects of their team's clients, members the projects of the
     * clients they brought and the projects they help on (same rule as ProjectPolicy::view).
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->isAdmin()) {
            return;
        }

        $query->where(fn (Builder $query) => $query
            ->whereHas('client.team', fn (Builder $team) => $team->where('leader_id', $user->id))
            ->orWhereHas('client', fn (Builder $client) => $client
                ->where('created_by', $user->id)
                ->whereIn('team_id', $user->teamIds()))
            ->orWhereHas('assignees', fn (Builder $assignee) => $assignee->whereKey($user->id)));
    }
}

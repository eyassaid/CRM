<?php

namespace App\Http\Requests\Users;

use App\Enums\UserRole;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'password' => [$user instanceof User ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'role' => $this->roleRules($user),
            'team_id' => $user instanceof User ? ['prohibited'] : $this->teamRules(),
        ];
    }

    /**
     * Admins choose employee or team leader (default employee); team leaders can only add employees.
     * A user who leads a team keeps the team leader role until their teams get another leader.
     *
     * @return array<int, mixed>
     */
    private function roleRules(?User $user): array
    {
        if (! $this->user()->isAdmin()) {
            return ['prohibited'];
        }

        return [
            'sometimes',
            Rule::in([UserRole::Employee->value, UserRole::TeamLeader->value]),
            function (string $attribute, mixed $value, Closure $fail) use ($user): void {
                if ($user?->isTeamLeader() && $value !== UserRole::TeamLeader->value && $user->ledTeams()->exists()) {
                    $fail('This user leads a team. Give their teams another leader first.');
                }
            },
        ];
    }

    /**
     * A new user joins a team: any team for admins, and one of the teams they lead (required) for team leaders.
     *
     * @return array<int, mixed>
     */
    private function teamRules(): array
    {
        $creator = $this->user();

        if ($creator->isAdmin()) {
            return ['nullable', 'integer', Rule::exists('teams', 'id')];
        }

        // UserPolicy refuses everyone else; skip the rule so they get a 403 instead of a 422.
        if (! $creator->isTeamLeader()) {
            return ['exclude'];
        }

        return ['required', 'integer', Rule::in($creator->ledTeams()->pluck('id')->all())];
    }
}

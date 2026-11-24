<?php

namespace App\Http\Requests\Teams;

use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeamRequest extends FormRequest
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
        return [
            // Only users with the team leader role can lead a team.
            'leader_id' => ['required', 'int', Rule::exists('users', 'id')->where('role', UserRole::TeamLeader->value)],
            'section_id' => ['required', 'int', Rule::exists('sections', 'id')],
            'name' => ['required', 'string'],
            'members_ids' => ['nullable', 'array'],
            'members_ids.*' => ['int', 'distinct', Rule::exists('users', 'id')],
        ];
    }
}

<?php

namespace App\Http\Requests\Projects;

use App\Enums\ProjectStatus;
use App\Http\Requests\Invoices\InvoiceRequest;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectRequest extends FormRequest
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
     * The client comes from the route on store and cannot be changed on update,
     * so a project cannot be moved to a client of another team.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Optional on update, so the people on a project can be changed on their own.
            'name' => [$this->route('project') instanceof Project ? 'sometimes' : 'required', 'string'],
            // A project is created unfinished: marking it done needs the invoice items below.
            'status' => ['sometimes', $this->route('project') instanceof Project
                ? Rule::enum(ProjectStatus::class)
                : Rule::enum(ProjectStatus::class)->except(ProjectStatus::Done)],
            'image' => ['sometimes', 'file', 'mimes:png,jpg,jpeg,webp'],
            // The people working on the project: the leader or members of the client's team.
            'user_ids' => ['sometimes', 'array'],
            'user_ids.*' => ['integer', 'distinct', Rule::in($this->assignableUserIds())],
            // Approves a project a member requested (admins and the team leader only).
            'approved' => ['sometimes', 'accepted'],
            // Marking a project done creates its invoice from these lines (at least one). Ignored otherwise.
            ...($this->completesProject() ? InvoiceRequest::itemRules() : []),
        ];
    }

    /**
     * Whether this update moves the project to done (it was not done before).
     */
    public function completesProject(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project
            && $this->input('status') === ProjectStatus::Done->value
            && $project->status !== ProjectStatus::Done;
    }

    /**
     * @return array<int, int>
     */
    private function assignableUserIds(): array
    {
        $project = $this->route('project');
        $client = $project instanceof Project ? $project->client : $this->route('client');
        $team = $client instanceof Client ? $client->team : null;

        if (! $team) {
            return [];
        }

        return $team->members()->pluck('users.id')->push($team->leader_id)->filter()->unique()->values()->all();
    }
}

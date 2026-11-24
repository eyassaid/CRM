<?php

namespace App\Http\Requests\Clients;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClientUpdateRequest extends FormRequest
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
        $client = $this->route('client');

        return [
            'name' => ['sometimes', 'string'],
            'phone_number' => ['sometimes', 'string', Rule::unique('clients', 'phone_number')->ignore($client)],
            'image' => ['sometimes', 'file', 'mimes:png,jpg,jpeg,webp'],
            // Same rule as ClientStoreRequest: non-admins can only move a client to one of their own teams.
            'team_id' => ['sometimes', 'integer', $this->user()->isAdmin()
                ? Rule::exists('teams', 'id')
                : Rule::in($this->user()->teamIds()->all())],
        ];
    }
}

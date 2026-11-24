<?php

namespace App\Http\Requests\Clients;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClientStoreRequest extends FormRequest
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
            'name' => ['required', 'string'],
            'phone_number' => ['required', 'string', 'unique:clients,phone_number'],
            'image' => ['sometimes', 'file', 'mimes:png,jpg,jpeg,webp'],
            // Admins can assign any team; everyone else only a team they are a member or the leader of.
            'team_id' => ['required', 'integer', $this->user()->isAdmin()
                ? Rule::exists('teams', 'id')
                : Rule::in($this->user()->teamIds()->all())],
        ];
    }
}

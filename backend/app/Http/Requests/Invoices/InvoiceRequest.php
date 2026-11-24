<?php

namespace App\Http\Requests\Invoices;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class InvoiceRequest extends FormRequest
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
        return self::itemRules();
    }

    /**
     * The invoice lines (at least one); also used when marking a project done (ProjectRequest).
     *
     * @return array<string, array<int, string>>
     */
    public static function itemRules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['array:name,cost'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.cost' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ];
    }
}

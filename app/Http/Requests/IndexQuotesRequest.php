<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Quotes\QuoteStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexQuotesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(QuoteStatus::class)],
            'departure_from' => ['nullable', 'date'],
            'departure_to' => ['nullable', 'date', 'after_or_equal:departure_from'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.enum' => 'El estado debe ser Cotizado o Contratado.',
            'departure_to.after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
            'per_page.min' => 'La página debe mostrar al menos 5 registros.',
            'per_page.max' => 'La página no puede mostrar más de 50 registros.',
        ];
    }
}

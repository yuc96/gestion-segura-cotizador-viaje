<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Exception\InvalidTripPeriod;
use App\Domain\TripPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'first_name' => $this->trim('first_name'),
            'last_name' => $this->trim('last_name'),
            'identification' => $this->filled('identification')
                ? strtoupper(trim((string) $this->input('identification')))
                : $this->input('identification'),
            'email' => $this->filled('email')
                ? strtolower(trim((string) $this->input('email')))
                : $this->input('email'),
            'destination_code' => $this->filled('destination_code')
                ? strtoupper(trim((string) $this->input('destination_code')))
                : $this->input('destination_code'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'min:2', 'max:80', 'regex:/^[\p{L}\s\'.\-]+$/u'],
            'last_name' => ['required', 'string', 'min:2', 'max:80', 'regex:/^[\p{L}\s\'.\-]+$/u'],
            'identification' => ['required', 'string', 'min:5', 'max:20', 'regex:/^[A-Z0-9]+$/'],
            'email' => ['required', 'string', 'email:rfc', 'max:150'],
            'birth_date' => ['required', 'date', 'before:today'],
            'destination_code' => ['required', 'string', 'size:2', 'regex:/^[A-Z]{2}$/'],
            'departure_date' => ['required', 'date', 'after_or_equal:today'],
            'return_date' => ['required', 'date', 'after_or_equal:departure_date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'Ingresa los nombres del asegurado.',
            'first_name.min' => 'Los nombres deben tener al menos 2 caracteres.',
            'first_name.regex' => 'Los nombres solo pueden contener letras.',
            'last_name.required' => 'Ingresa los apellidos del asegurado.',
            'last_name.min' => 'Los apellidos deben tener al menos 2 caracteres.',
            'last_name.regex' => 'Los apellidos solo pueden contener letras.',
            'identification.required' => 'Ingresa el número de identificación.',
            'identification.min' => 'La identificación debe tener al menos 5 caracteres.',
            'identification.regex' => 'La identificación solo puede contener letras y números.',
            'email.required' => 'Ingresa el correo electrónico.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'birth_date.required' => 'Ingresa la fecha de nacimiento.',
            'birth_date.date' => 'La fecha de nacimiento no es válida.',
            'birth_date.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
            'destination_code.required' => 'Selecciona el país de destino.',
            'destination_code.size' => 'El país de destino no es válido.',
            'destination_code.regex' => 'El país de destino no es válido.',
            'departure_date.required' => 'Ingresa la fecha de salida.',
            'departure_date.date' => 'La fecha de salida no es válida.',
            'departure_date.after_or_equal' => 'La salida no puede ser anterior a hoy.',
            'return_date.required' => 'Ingresa la fecha de regreso.',
            'return_date.date' => 'La fecha de regreso no es válida.',
            'return_date.after_or_equal' => 'El regreso no puede ser anterior a la salida.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->hasAny(['birth_date', 'departure_date', 'return_date'])) {
                return;
            }

            $birth = CarbonImmutable::parse((string) $this->input('birth_date'))->startOfDay();
            $departure = CarbonImmutable::parse((string) $this->input('departure_date'))->startOfDay();
            $return = CarbonImmutable::parse((string) $this->input('return_date'))->startOfDay();

            if ($birth->gt($departure)) {
                $validator->errors()->add('birth_date', 'La fecha de nacimiento no puede ser posterior a la salida.');

                return;
            }

            $age = $birth->diff($departure)->y;

            if ($age < 18) {
                $validator->errors()->add('birth_date', 'El asegurado debe tener al menos 18 años en la fecha de salida.');
            }

            if ($age > 85) {
                $validator->errors()->add('birth_date', 'El asegurado no puede superar los 85 años en la fecha de salida.');
            }

            try {
                TripPeriod::inclusive($departure, $return);
            } catch (InvalidTripPeriod $exception) {
                $validator->errors()->add('return_date', $exception->getMessage());
            }
        });
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Revisa los datos de la cotización.',
            'errors' => $validator->errors(),
        ], 422));
    }

    private function trim(string $key): mixed
    {
        if (! $this->filled($key) || ! is_string($this->input($key))) {
            return $this->input($key);
        }

        return trim($this->input($key));
    }
}

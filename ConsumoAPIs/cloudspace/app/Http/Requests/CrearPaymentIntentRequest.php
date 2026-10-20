<?php

namespace App\Http\Requests;

use App\Services\StripeService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class CrearPaymentIntentRequest extends FormRequest
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
            'monto' => ['required', 'numeric', 'decimal:0,2', 'gt:0'],
            'moneda' => ['required', 'string', Rule::in(array_keys(StripeService::MONEDAS))],
            'nombre' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'concepto' => ['nullable', 'string', 'max:150'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('moneda'))) {
            $this->merge(['moneda' => strtolower(trim($this->input('moneda')))]);
        }
    }

    /**
     * La creación del PaymentIntent es una operación asíncrona (fetch): los datos
     * inválidos se responden en JSON con 400 Bad Request (RNF 3), no con una redirección.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Revisa los datos del pago.',
            'errors' => $validator->errors(),
        ], 400));
    }
}

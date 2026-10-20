<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class EnviarCorreoRequest extends FormRequest
{
    public const MAX_DESTINATARIOS = 10;

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
            // Uno o varios correos separados por comas, punto y coma o saltos de línea
            'destinatarios' => ['required', 'string', 'max:2000', function (string $atributo, mixed $valor, Closure $fallar) {
                $correos = $this->separarCorreos((string) $valor);

                if (count($correos) > self::MAX_DESTINATARIOS) {
                    $fallar('Puedes enviar a máximo '.self::MAX_DESTINATARIOS.' destinatarios a la vez.');

                    return;
                }

                $invalidos = array_filter($correos, fn (string $correo) => ! filter_var($correo, FILTER_VALIDATE_EMAIL));

                if ($invalidos !== []) {
                    $fallar('Estos destinatarios no son correos válidos: '.implode(', ', $invalidos).'.');
                }
            }],
            'asunto' => ['required', 'string', 'max:150'],
            'mensaje' => ['required', 'string', 'max:5000'],
        ];
    }

    /**
     * Lista limpia de destinatarios (sin repetidos) para el servicio de envío.
     *
     * @return array<int, string>
     */
    public function destinatarios(): array
    {
        return $this->separarCorreos((string) $this->validated('destinatarios'));
    }

    /**
     * @return array<int, string>
     */
    private function separarCorreos(string $texto): array
    {
        return collect(preg_split('/[\s,;]+/', $texto, -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn (string $correo) => mb_strtolower($correo))
            ->unique()
            ->values()
            ->all();
    }
}

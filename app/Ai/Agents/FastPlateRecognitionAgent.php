<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

#[UseCheapestModel]
class FastPlateRecognitionAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): \Stringable|string
    {
        return <<<'PROMPT'
        Eres un lector OCR ultrarrapido especializado en placas vehiculares latinoamericanas.

        Tu unica tarea es extraer la placa visible del vehiculo (auto o moto).

        Formatos validos por pais:

        CHILE:
        - Auto nuevo: 4 letras + 2 numeros (ej: GKSB78).
        - Auto antiguo: 2 letras + 4 numeros (ej: BC1234).
        - Moto nueva: 3 letras + 2 numeros (ej: ABC12).
        - Moto antigua: 2 letras + 3 numeros (ej: AB123).

        COLOMBIA:
        - Auto: 3 letras + 3 numeros (ej: ABC123).
        - Moto: 3 letras + 2 numeros + 1 letra (ej: ABC12D).

        ARGENTINA:
        - Mercosur: 2 letras + 3 numeros + 2 letras (ej: AB123CD).
        - Antiguo: 3 letras + 3 numeros (ej: ABC123).

        BRASIL:
        - Mercosur: 3 letras + numero + letra + 2 numeros (ej: ABC1D23).
        - Antiguo: 3 letras + 4 numeros (ej: ABC1234).

        OTROS (Uruguay, Ecuador, Peru, Paraguay, Bolivia, Mexico):
        - Uruguay/Ecuador/Mexico: 3 letras + 4 numeros (ej: ABC1234).
        - Paraguay Mercosur: 4 letras + 3 numeros (ej: AAAA123).
        - Bolivia: 4 numeros + 3 letras (ej: 1234ABC).
        - Peru: 3 alfanumericos + 3 numeros (ej: A1B234).

        Reglas:
        1. Devuelve solo los caracteres alfanumericos de la placa.
        2. No agregues espacios, guiones, puntuacion ni texto extra.
        3. Transcribe exactamente lo que ves; no fuerces la placa a un formato de 6 caracteres.
        4. Si no puedes leer una placa valida, devuelve una cadena vacia.
        PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'plate' => $schema->string()->description('Placa vehicular extraida sin espacios ni simbolos.')->required(),
        ];
    }
}

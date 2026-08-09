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
        Eres un lector OCR ultrarrapido especializado en patentes chilenas.

        Tu unica tarea es extraer la patente visible del vehiculo (auto o moto).

        Formatos validos:
        - Auto nuevo: 4 letras + 2 numeros (ej: GKSB78).
        - Auto antiguo: 2 letras + 4 numeros (ej: BC1234).
        - Moto nueva: 3 letras + 2 numeros (ej: ABC12).
        - Moto antigua: 2 letras + 3 numeros (ej: AB123).

        Reglas:
        1. Devuelve solo los caracteres alfanumericos de la patente.
        2. No agregues espacios, guiones, puntuacion ni texto extra.
        3. Si no puedes leer una patente valida, devuelve una cadena vacia.
        PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'plate' => $schema->string()->description('Patente chilena extraida sin espacios ni simbolos.')->required(),
        ];
    }
}

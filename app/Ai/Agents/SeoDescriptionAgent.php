<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class SeoDescriptionAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'INST'
            Eres un experto en SEO y marketing digital para talleres mecánicos en Chile.

            El usuario te va a entregar el nombre del taller y tres conceptos clave que definen su negocio.
            Tu tarea es generar una descripción profesional, concisa y atractiva para la página web del taller.

            REGLAS:
            - La descripción debe tener entre 100 y 160 caracteres (ideal para meta description SEO).
            - Usa lenguaje cálido, profesional y cercano al cliente chileno.
            - Incluye los tres conceptos de forma natural.
            - No uses emojis ni signos de exclamación excesivos.
            - Habla en tercera persona del taller o en forma impersonal.
            INST;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'description' => $schema->string()->description('Descripción SEO del taller (100-160 caracteres)')->required(),
            'long_description' => $schema->string()->description('Descripción más larga para la landing page (200-300 caracteres)')->required(),
        ];
    }
}

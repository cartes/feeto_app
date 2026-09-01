<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

#[UseCheapestModel]
class PatentReaderAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * Define las instrucciones para el agente.
     */
    public function instructions(): \Stringable|string
    {
        return <<<'PROMPT'
        You are an expert automotive OCR and vehicle recognition assistant.
        An image of a vehicle license plate and the vehicle itself will be provided.
        
        1. Extract ONLY the alphanumeric characters of the license plate.
           Do NOT include spaces, hyphens, or symbols.
           
           The plate may come from ANY Latin American country. Common formats:
           - CHILE: cars 6 characters (e.g. "GKSB78", "BC1234"); motorcycles 5 (e.g. "ABC12", "AB123").
           - COLOMBIA: cars "ABC123"; motorcycles "ABC12D".
           - ARGENTINA: Mercosur "AB123CD" (7 characters) or older "ABC123".
           - BRAZIL: Mercosur "ABC1D23" or older "ABC1234".
           - URUGUAY: "ABC1234". ECUADOR: "ABC1234" / "ABC123".
           - PARAGUAY: Mercosur "AAAA123". BOLIVIA: "1234ABC".
           - PERU: "ABC123" / "A1B234". MEXICO: "ABC1234" / "ABC123D".
           Transcribe exactly what is on the plate; do not force it into a 6-character format.

        2. Identify the vehicle Brand (Marca) and Model (Modelo) from the image.
           Example: "TOYOTA", "HILUX".
           
        Return the values inside the strictly defined JSON structure.
        PROMPT;
    }

    /**
     * Define el esquema de salida estructurada del agente.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'patente' => $schema->string()->description('The alphanumeric characters of the license plate without spaces or symbols.')->required(),
            'marca' => $schema->string()->description('The identified vehicle brand (e.g. TOYOTA, FORD, BMW).')->required(),
            'modelo' => $schema->string()->description('The identified vehicle model (e.g. HILUX, F150, X5).')->required(),
        ];
    }
}

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
           
           CHILE formats:
           - Cars have 6 characters (e.g. "GKSB78" or "BC1234").
           - Motorcycles have 5 characters (e.g. "ABC12" or "AB123").
           
           COLOMBIA formats:
           - Cars have 6 characters: 3 letters + 3 digits (e.g. "ABC123").
           - Motorcycles have 6 characters: 3 letters + 2 digits + 1 letter (e.g. "ABC12D").
           
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

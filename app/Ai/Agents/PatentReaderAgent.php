<?php
declare(strict_types=1);

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

class PatentReaderAgent implements Agent, HasStructuredOutput
{
    use Promptable;
    /**
     * Define the instructions for the agent.
     */
    public function instructions(): \Stringable|string
    {
        return <<<'PROMPT'
        You are an expert automotive OCR and vehicle recognition assistant.
        An image of a vehicle license plate and the vehicle itself will be provided.
        
        1. Extract ONLY the alphanumeric characters of the Chilean license plate (PPU).
           Do NOT include spaces, hyphens, or symbols. Example: "GKSB78".
           
        2. Identify the vehicle Brand (Marca) and Model (Modelo) from the image.
           Example: "TOYOTA", "HILUX".
           
        Return the values inside the strictly defined JSON structure.
        PROMPT;
    }

    /**
     * Define the structured output schema for the agent.
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

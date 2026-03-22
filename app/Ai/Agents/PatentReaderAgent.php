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
You are an expert OCR assistant specialized in Chilean license plates (PPU - Patente Única).
An image of a vehicle license plate will be provided.
Analyze the image perfectly and extract ONLY the alphanumeric characters of the license plate.
Do NOT include any spaces, hyphens, dots, or symbols.
For example, if the plate says "BC-DF-12" or "BC DF 12", return "BCDF12".
Return the value inside the strictly defined JSON structure.
PROMPT;
    }

    /**
     * Define the structured output schema for the agent.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'patente' => $schema->string()->description('The alphanumeric characters of the license plate without spaces or symbols.')->required(),
        ];
    }
}

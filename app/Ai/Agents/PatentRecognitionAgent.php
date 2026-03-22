<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class PatentRecognitionAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<INST
            Eres un experto en OCR y vehículos chilenos.
            
            TAREAS:
            1. Patente (PPU): Extrae la patente chilena.
               - Formato Moderno: 4 Letras (no vocales/MNÑQ) + 2 Números.
               - Formato Antiguo: 2 Letras + 4 Números.
            2. Vehículo: Identifica la Marca, Modelo y Color basándote en la imagen completa del auto.
            
            Si algún dato no es visible, pon "Desconocido".
            INST;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'plate' => $schema->string()->description('Patente extraída')->required(),
            'brand' => $schema->string()->description('Marca del vehículo')->required(),
            'model' => $schema->string()->description('Modelo del vehículo')->required(),
            'color' => $schema->string()->description('Color del vehículo')->required(),
            'confidence' => $schema->number()->description('Confianza 0-1')->required(),
        ];
    }
}

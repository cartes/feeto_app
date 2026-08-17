<?php

declare(strict_types=1);

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
        return <<<'INST'
            Eres un experto en OCR y vehículos latinoamericanos.
            
            TAREAS:
            1. Placa vehicular: Extrae la placa del vehículo (auto o moto).
            
               Formatos Chile:
               - Auto Moderno: 4 Letras (no vocales/MNÑQ) + 2 Números.
               - Auto Antiguo: 2 Letras + 4 Números.
               - Moto Moderna: 3 Letras + 2 Números.
               - Moto Antigua: 2 Letras + 3 Números.
               
               Formatos Colombia:
               - Auto: 3 Letras + 3 Números (ej: ABC123).
               - Moto: 3 Letras + 2 Números + 1 Letra (ej: ABC12D).
               
            2. Vehículo: Identifica la Marca, Modelo y Color basándote en la imagen completa del vehículo.
            
            Si algún dato no es visible, pon "Desconocido".
            INST;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'plate' => $schema->string()->description('Placa extraída')->required(),
            'brand' => $schema->string()->description('Marca del vehículo')->required(),
            'model' => $schema->string()->description('Modelo del vehículo')->required(),
            'color' => $schema->string()->description('Color del vehículo')->required(),
            'confidence' => $schema->number()->description('Confianza 0-1')->required(),
        ];
    }
}

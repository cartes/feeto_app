<?php

declare(strict_types=1);

namespace App\Services;

use App\Ai\Agents\SupportAssistantAgent;
use App\Support\SupportFaqCatalog;
use Illuminate\Support\Facades\Log;

class SupportAssistantService
{
    public function __construct(
        protected SupportAssistantAgent $agent
    ) {}

    /**
     * @return array{answer: string, selector: string|null, matched: bool}
     */
    public function ask(?string $section, string $question): array
    {
        $faqs = SupportFaqCatalog::forSection($section);

        if ($faqs === []) {
            return [
                'answer' => 'Por ahora no tengo preguntas frecuentes cargadas para esta vista. Prueba revisando el menú lateral o contacta a soporte si necesitas ayuda.',
                'selector' => null,
                'matched' => false,
            ];
        }

        try {
            $provider = (string) config('ai.default_for_support', config('ai.default', 'gemini'));

            $response = $this->agent->prompt(
                $this->buildPrompt($section, $faqs, $question),
                provider: $provider,
            );

            $answer = trim((string) ($response['answer'] ?? ''));
            $matchedId = trim((string) ($response['matched_faq_id'] ?? ''));

            if ($answer === '') {
                return $this->fallback();
            }

            $matchedFaq = $matchedId !== '' ? SupportFaqCatalog::find($section, $matchedId) : null;

            return [
                'answer' => $answer,
                'selector' => $matchedFaq['selector'] ?? null,
                'matched' => $matchedFaq !== null,
            ];
        } catch (\Throwable $e) {
            Log::error('Support assistant error: '.$e->getMessage());

            return $this->fallback();
        }
    }

    /**
     * @param  array<int, array{id: string, question: string, answer: string, selector: string|null}>  $faqs
     */
    protected function buildPrompt(?string $section, array $faqs, string $question): string
    {
        $context = collect($faqs)
            ->map(fn (array $faq) => "- id: {$faq['id']}\n  pregunta: {$faq['question']}\n  respuesta: {$faq['answer']}")
            ->implode("\n");

        return <<<PROMPT
            Vista actual: {$section}

            FAQ disponibles para esta vista:
            {$context}

            Pregunta del usuario: {$question}
            PROMPT;
    }

    /**
     * @return array{answer: string, selector: string|null, matched: bool}
     */
    protected function fallback(): array
    {
        return [
            'answer' => 'No pude procesar tu pregunta en este momento. Intenta de nuevo en unos segundos o contacta a soporte.',
            'selector' => null,
            'matched' => false,
        ];
    }
}

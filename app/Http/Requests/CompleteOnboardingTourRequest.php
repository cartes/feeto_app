<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteOnboardingTourRequest extends FormRequest
{
    /**
     * Secciones con mini tour propio. Debe coincidir con TallerLayout::SECTION_TOUR_ROUTES.
     *
     * @var list<string>
     */
    public const SECTIONS = [
        'dashboard',
        'reception',
        'work-orders',
        'quotes',
        'inventory',
        'services',
        'clients',
        'reports',
        'subscription-plans',
        'subscription-billing',
        'settings',
    ];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'section' => ['sometimes', 'string', Rule::in(self::SECTIONS)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'section.in' => 'La sección del tour no es válida.',
        ];
    }
}

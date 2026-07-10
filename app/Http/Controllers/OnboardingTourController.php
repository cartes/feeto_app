<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OnboardingTourController extends Controller
{
    public function __invoke(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $section = $request->validate([
            'section' => ['sometimes', 'string', 'max:64'],
        ])['section'] ?? null;

        if ($section !== null) {
            $sections = $user->onboarding_sections_completed ?? [];

            if (! in_array($section, $sections, true)) {
                $sections[] = $section;

                $user->forceFill([
                    'onboarding_sections_completed' => $sections,
                ])->save();
            }

            return response()->noContent();
        }

        if ($user->onboarding_tour_completed_at === null) {
            $user->forceFill([
                'onboarding_tour_completed_at' => now(),
            ])->save();
        }

        return response()->noContent();
    }
}

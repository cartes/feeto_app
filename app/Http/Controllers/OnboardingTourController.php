<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CompleteOnboardingTourRequest;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class OnboardingTourController extends Controller
{
    public function __invoke(CompleteOnboardingTourRequest $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $section = $request->validated('section');

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

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TenantSchedulingController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'slot_duration' => 'required|integer|in:15,30,45,60,90,120',
            'days' => 'required|array',
            'days.*.enabled' => 'required|boolean',
            'days.*.open' => 'nullable|date_format:H:i',
            'days.*.close' => 'nullable|date_format:H:i',
            'blocked_slots' => 'nullable|array',
            'blocked_slots.*.id' => 'nullable|string|max:64',
            'blocked_slots.*.day' => 'required|string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday,all',
            'blocked_slots.*.start' => 'required|date_format:H:i',
            'blocked_slots.*.end' => 'required|date_format:H:i',
            'blocked_slots.*.reason' => 'nullable|string|max:100',
        ]);

        $allowedDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $days = collect($validated['days'])
            ->only($allowedDays)
            ->map(fn (array $day): array => [
                'enabled' => (bool) $day['enabled'],
                'open' => $day['open'] ?? '09:00',
                'close' => $day['close'] ?? '18:00',
            ])
            ->all();

        $blockedSlots = collect($validated['blocked_slots'] ?? [])
            ->values()
            ->map(fn (array $slot): array => [
                'id' => $slot['id'] ?? uniqid('slot_', true),
                'day' => $slot['day'],
                'start' => $slot['start'],
                'end' => $slot['end'],
                'reason' => $slot['reason'] ?? '',
            ])
            ->all();

        Tenant::current()->update([
            'scheduling_config' => [
                'slot_duration' => (int) $validated['slot_duration'],
                'days' => $days,
                'blocked_slots' => $blockedSlots,
            ],
        ]);

        return back()->with('success', 'Horarios del taller actualizados correctamente.');
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\ChileHolidayService;
use Illuminate\Http\JsonResponse;
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
            'days.*.open' => 'nullable|date_format:H:i,H:i:s',
            'days.*.close' => 'nullable|date_format:H:i,H:i:s',
            'blocked_slots' => 'nullable|array',
            'blocked_slots.*.id' => 'nullable|string|max:64',
            'blocked_slots.*.date' => 'required|date_format:Y-m-d',
            'blocked_slots.*.start' => 'required|date_format:H:i,H:i:s',
            'blocked_slots.*.end' => 'required|date_format:H:i,H:i:s',
            'blocked_slots.*.reason' => 'nullable|string|max:100',
            'blocked_dates' => 'nullable|array',
            'blocked_dates.*.date' => 'required|date_format:Y-m-d',
            'blocked_dates.*.label' => 'required|string|max:120',
            'blocked_dates.*.type' => 'required|string|in:official,custom',
        ]);

        $allowedDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $days = collect($validated['days'])
            ->only($allowedDays)
            ->map(fn (array $day): array => [
                'enabled' => (bool) $day['enabled'],
                'open' => ! empty($day['open']) ? substr($day['open'], 0, 5) : '09:00',
                'close' => ! empty($day['close']) ? substr($day['close'], 0, 5) : '18:00',
            ])
            ->all();

        $blockedSlots = collect($validated['blocked_slots'] ?? [])
            ->values()
            ->map(fn (array $slot): array => [
                'id' => $slot['id'] ?? uniqid('slot_', true),
                'date' => $slot['date'],
                'start' => substr($slot['start'], 0, 5),
                'end' => substr($slot['end'], 0, 5),
                'reason' => $slot['reason'] ?? '',
            ])
            ->sortBy(fn (array $s): string => $s['date'].$s['start'])
            ->values()
            ->all();

        $blockedDates = collect($validated['blocked_dates'] ?? [])
            ->unique('date')
            ->sortBy('date')
            ->values()
            ->map(fn (array $d): array => [
                'date' => $d['date'],
                'label' => $d['label'],
                'type' => $d['type'],
            ])
            ->all();

        Tenant::current()->update([
            'scheduling_config' => [
                'slot_duration' => (int) $validated['slot_duration'],
                'days' => $days,
                'blocked_slots' => $blockedSlots,
                'blocked_dates' => $blockedDates,
            ],
        ]);

        return back()->with('success', 'Horarios del taller actualizados correctamente.');
    }

    public function feriados(Request $request, ChileHolidayService $service): JsonResponse
    {
        $year = (int) $request->query('year', now()->year);
        $year = max(2020, min($year, now()->year + 2));

        $holidays = $service->forYear($year);

        return response()->json($holidays);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ApiUsageLog;
use App\Services\SupportAssistantService;
use App\Support\SupportFaqCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Multitenancy\Models\Tenant;

class SupportAssistantController extends Controller
{
    public function __construct(
        protected SupportAssistantService $supportAssistant
    ) {}

    public function faq(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'section' => ['nullable', 'string', 'in:'.implode(',', array_keys(SupportFaqCatalog::all()))],
        ]);

        return response()->json([
            'faqs' => SupportFaqCatalog::forSection($validated['section'] ?? null),
        ]);
    }

    public function ask(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'section' => ['nullable', 'string', 'in:'.implode(',', array_keys(SupportFaqCatalog::all()))],
            'question' => ['required', 'string', 'max:300'],
        ]);

        $result = $this->supportAssistant->ask($validated['section'] ?? null, $validated['question']);

        ApiUsageLog::record('support_chat', Tenant::current()?->id);

        return response()->json($result);
    }
}

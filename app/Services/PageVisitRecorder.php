<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PageVisit;
use App\Models\PageVisitVisitor;
use App\Models\Tenant as AppTenant;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Multitenancy\Models\Tenant as CurrentTenant;
use Symfony\Component\HttpFoundation\Response;

/**
 * Decide si una petición cuenta como "página vista" y la registra en:
 *  - page_visits: agregado por página/día (visitas y únicos por página).
 *  - page_visit_visitors: una fila por persona/día/ámbito (usuarios únicos reales).
 */
class PageVisitRecorder
{
    /** Prefijos de ruta que nunca cuentan como visita. */
    private const EXCLUDED_PATHS = ['_*', 'api/*', 'admin/*', 'storage/*', 'build/*', 'up', 'livewire/*', 'sanctum/*', 'sitemap*', 'robots.txt', 'favicon.ico'];

    /** Rutas nombradas que son páginas públicas de un taller. */
    private const TENANT_PUBLIC_ROUTES = ['taller.landing', 'checkout.', 'quotes.public.'];

    private const BOT_PATTERN = '/bot|crawl|spider|slurp|facebookexternalhit|facebot|whatsapp|telegram|discord|preview|headless|lighthouse|pingdom|uptime|monitor|curl\/|wget|python-requests|go-http-client|java\/|okhttp|scrapy|ahrefs|semrush|mj12|dotbot|petalbot|bytespider|gptbot|claudebot|anthropic|ccbot|applebot|yandex|baidu|duckduck|bingpreview|embedly|quora|outbrain|vkshare|w3c_validator|skypeuripreview|nuzzel|qwantify|bitlybot|redditbot|slackbot|linkedinbot|twitterbot|pinterest/i';

    public function record(Request $request, Response $response): void
    {
        try {
            if (! $this->shouldTrack($request, $response)) {
                return;
            }

            $path = $request->path() ?: '/';
            $tenantId = $this->resolveTenantId($request);
            $scope = $this->resolveScope($request);
            $isUniqueForPath = $this->markPathVisitedInSession($request, $path);

            PageVisit::record($path, $tenantId, $isUniqueForPath, $scope);

            $this->recordVisitor($request, $path, $tenantId, $scope);
        } catch (\Throwable $e) {
            Log::warning('No se pudo registrar la visita de página.', [
                'path' => $request->path(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function shouldTrack(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET') || ! $response->isSuccessful()) {
            return false;
        }

        if ($request->is(...self::EXCLUDED_PATHS)) {
            return false;
        }

        // El super-admin navegando su propia plataforma no es tráfico real.
        if ($request->user()?->is_super_admin) {
            return false;
        }

        // Prefetch del navegador o de Inertia: no es una vista real.
        $purpose = strtolower((string) ($request->headers->get('Purpose') ?? $request->headers->get('Sec-Purpose') ?? ''));
        if (str_contains($purpose, 'prefetch') || str_contains($purpose, 'prerender')) {
            return false;
        }

        if ($this->isBot((string) $request->userAgent())) {
            return false;
        }

        // Navegación Inertia (SPA): cuenta salvo que sea una recarga parcial de props.
        if ($request->headers->has('X-Inertia')) {
            return ! $request->headers->has('X-Inertia-Partial-Component');
        }

        // Peticiones XHR/JSON (polling de notificaciones, buscadores, APIs internas) no cuentan.
        if ($request->ajax() || $request->expectsJson()) {
            return false;
        }

        $contentType = (string) $response->headers->get('Content-Type', 'text/html');

        return str_contains($contentType, 'text/html');
    }

    public function isBot(string $userAgent): bool
    {
        if ($userAgent === '') {
            return false;
        }

        return preg_match(self::BOT_PATTERN, $userAgent) === 1;
    }

    public function resolveScope(Request $request): string
    {
        if ($request->user() !== null) {
            return PageVisit::SCOPE_APP;
        }

        $routeName = (string) ($request->route()?->getName() ?? '');

        foreach (self::TENANT_PUBLIC_ROUTES as $prefix) {
            if ($routeName === $prefix || str_starts_with($routeName, $prefix)) {
                return PageVisit::SCOPE_TENANT;
            }
        }

        return PageVisit::SCOPE_SITE;
    }

    public function detectDevice(string $userAgent): string
    {
        if ($userAgent === '') {
            return 'desktop';
        }

        if (preg_match('/ipad|tablet|kindle|silk|playbook|(android(?!.*mobile))/i', $userAgent)) {
            return 'tablet';
        }

        if (preg_match('/mobile|iphone|ipod|android|windows phone|blackberry|opera mini|iemobile/i', $userAgent)) {
            return 'mobile';
        }

        return 'desktop';
    }

    /**
     * Origen del tráfico: utm_source si existe, si no el host del Referer externo.
     */
    public function resolveReferrer(Request $request): ?string
    {
        $utm = trim((string) $request->query('utm_source', ''));
        if ($utm !== '') {
            return Str::limit(Str::lower($utm), 120, '');
        }

        $referer = (string) $request->headers->get('referer', '');
        if ($referer === '') {
            return null;
        }

        $host = strtolower((string) parse_url($referer, PHP_URL_HOST));
        if ($host === '') {
            return null;
        }

        $host = preg_replace('/^www\./', '', $host) ?? $host;
        $ownHost = preg_replace('/^www\./', '', strtolower($request->getHost())) ?? '';

        if ($host === $ownHost) {
            return null;
        }

        return Str::limit($host, 120, '');
    }

    public function visitorHash(Request $request): string
    {
        $user = $request->user();

        $identity = $user !== null
            ? 'user:'.$user->getAuthIdentifier()
            : 'guest:'.$request->ip().'|'.(string) $request->userAgent();

        return substr(hash('sha256', config('app.key').'|'.$identity), 0, 40);
    }

    private function markPathVisitedInSession(Request $request, string $path): bool
    {
        if (! $request->hasSession()) {
            return false;
        }

        $sessionKey = 'visited_paths.'.now()->toDateString();
        $visitedPaths = $request->session()->get($sessionKey, []);

        if (in_array($path, $visitedPaths, true)) {
            return false;
        }

        $visitedPaths[] = $path;
        $request->session()->put($sessionKey, $visitedPaths);

        return true;
    }

    private function recordVisitor(Request $request, string $path, ?int $tenantId, string $scope): void
    {
        $keys = [
            'date' => today(),
            'visitor_hash' => $this->visitorHash($request),
            'scope' => $scope,
        ];

        $existing = PageVisitVisitor::query()->where($keys)->first();

        if ($existing) {
            $existing->increment('page_views', 1, ['last_seen_at' => now()]);

            return;
        }

        try {
            PageVisitVisitor::create([
                ...$keys,
                'tenant_id' => $tenantId,
                'device' => $this->detectDevice((string) $request->userAgent()),
                'referrer' => $this->resolveReferrer($request),
                'entry_path' => Str::limit($path, 255, ''),
                'page_views' => 1,
                'first_seen_at' => now(),
                'last_seen_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            PageVisitVisitor::query()->where($keys)->increment('page_views', 1, ['last_seen_at' => now()]);
        }
    }

    private function resolveTenantId(Request $request): ?int
    {
        if (CurrentTenant::checkCurrent()) {
            return CurrentTenant::current()?->id;
        }

        $routeTenant = $request->route('tenantBySlug');

        if ($routeTenant instanceof AppTenant) {
            return $routeTenant->id;
        }

        if (is_string($routeTenant) && $routeTenant !== '') {
            return AppTenant::query()
                ->where('slug', $routeTenant)
                ->value('id');
        }

        return $request->user()?->tenant_id;
    }
}

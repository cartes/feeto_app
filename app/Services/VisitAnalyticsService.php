<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PageVisit;
use App\Models\PageVisitVisitor;
use App\Models\Tenant;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Consultas de analítica de visitas para el super-admin.
 *
 * Un "período" es un rango de días cerrado [from, to]; el período anterior
 * es el mismo número de días inmediatamente antes de `from`.
 */
class VisitAnalyticsService
{
    /** @var array<string, int> */
    public const PERIODS = ['7d' => 7, '30d' => 30, '90d' => 90, '12m' => 365];

    public const SCOPE_ALL = 'all';

    private ?string $trackingSince = null;

    private bool $trackingSinceLoaded = false;

    /** @var list<string> */
    public const SCOPES = [self::SCOPE_ALL, PageVisit::SCOPE_SITE, PageVisit::SCOPE_TENANT, PageVisit::SCOPE_APP];

    public static function normalizePeriod(?string $period, string $default = '30d'): string
    {
        return array_key_exists((string) $period, self::PERIODS) ? (string) $period : $default;
    }

    public static function normalizeScope(?string $scope, string $default = self::SCOPE_ALL): string
    {
        return in_array((string) $scope, self::SCOPES, true) ? (string) $scope : $default;
    }

    /**
     * @return array{from: CarbonImmutable, to: CarbonImmutable, days: int}
     */
    public function range(string $period): array
    {
        $days = self::PERIODS[self::normalizePeriod($period)];
        $to = CarbonImmutable::today();

        return ['from' => $to->subDays($days - 1), 'to' => $to, 'days' => $days];
    }

    /**
     * Resumen completo para la vista de analítica.
     *
     * @return array<string, mixed>
     */
    public function report(string $period, string $scope): array
    {
        $period = self::normalizePeriod($period);
        $scope = self::normalizeScope($scope);
        $range = $this->range($period);
        $previous = [
            'from' => $range['from']->subDays($range['days']),
            'to' => $range['from']->subDay(),
        ];

        $byDay = $this->byDay($range['from'], $range['to'], $scope);

        return [
            'period' => $period,
            'scope' => $scope,
            'range' => [
                'from' => $range['from']->toDateString(),
                'to' => $range['to']->toDateString(),
                'days' => $range['days'],
                'tracking_since' => $this->trackingSince(),
            ],
            'summary' => $this->summary($range['from'], $range['to'], $previous['from'], $previous['to'], $scope, $byDay),
            'by_day' => $byDay,
            'by_scope' => $this->byScope($range['from'], $range['to']),
            'top_pages' => $this->topPages($range['from'], $range['to'], $scope),
            'entry_pages' => $this->entryPages($range['from'], $range['to'], $scope),
            'referrers' => $this->referrers($range['from'], $range['to'], $scope),
            'devices' => $this->devices($range['from'], $range['to'], $scope),
            'by_weekday' => $this->byWeekday($byDay),
            'by_tenant' => $this->byTenant($range['from'], $range['to']),
        ];
    }

    /**
     * Versión reducida para la tarjeta del panel principal.
     *
     * @return array<string, mixed>
     */
    public function dashboardSnapshot(string $period, string $scope): array
    {
        $period = self::normalizePeriod($period);
        $scope = self::normalizeScope($scope, PageVisit::SCOPE_SITE);
        $range = $this->range($period);
        $byDay = $this->byDay($range['from'], $range['to'], $scope);

        return [
            'period' => $period,
            'scope' => $scope,
            'range' => [
                'from' => $range['from']->toDateString(),
                'to' => $range['to']->toDateString(),
                'days' => $range['days'],
                'tracking_since' => $this->trackingSince(),
            ],
            'summary' => $this->summary(
                $range['from'],
                $range['to'],
                $range['from']->subDays($range['days']),
                $range['from']->subDay(),
                $scope,
                $byDay,
            ),
            'by_day' => $byDay,
            'by_scope' => $this->byScope($range['from'], $range['to']),
        ];
    }

    /**
     * Serie diaria completa (rellena días sin datos con 0).
     *
     * @return list<array{date: string, visits: int, unique_visitors: int|null}>
     */
    public function byDay(CarbonImmutable $from, CarbonImmutable $to, string $scope = self::SCOPE_ALL): array
    {
        $visits = $this->visitsQuery($from, $to, $scope)
            ->select('date', DB::raw('sum(visits) as total'))
            ->groupBy('date')
            ->pluck('total', 'date')
            ->mapWithKeys(fn ($total, $date) => [$this->dateKey($date) => (int) $total]);

        $visitorsQuery = $this->visitorsQuery($from, $to, $scope)->select('date');
        $visitorsQuery = $scope === self::SCOPE_ALL
            ? $visitorsQuery->addSelect(DB::raw('count(distinct visitor_hash) as total'))
            : $visitorsQuery->addSelect(DB::raw('count(*) as total'));

        $visitors = $visitorsQuery
            ->groupBy('date')
            ->pluck('total', 'date')
            ->mapWithKeys(fn ($total, $date) => [$this->dateKey($date) => (int) $total]);

        $trackingSince = $this->trackingSince();

        $series = [];
        foreach (CarbonPeriod::create($from, $to) as $day) {
            $key = $day->toDateString();
            $dayVisits = $visits[$key] ?? 0;
            $hasVisitorData = $trackingSince !== null && $key >= $trackingSince;

            $series[] = [
                'date' => $key,
                'visits' => $dayVisits,
                // Antes de activar el registro de visitantes no hay datos de únicos: null en vez de 0.
                'unique_visitors' => $hasVisitorData || $dayVisits === 0 ? ($visitors[$key] ?? 0) : null,
            ];
        }

        return $series;
    }

    /**
     * @param  list<array{date: string, visits: int, unique_visitors: int|null}>  $byDay
     * @return array<string, mixed>
     */
    public function summary(
        CarbonImmutable $from,
        CarbonImmutable $to,
        CarbonImmutable $prevFrom,
        CarbonImmutable $prevTo,
        string $scope,
        array $byDay,
    ): array {
        $current = $this->totals($from, $to, $scope);
        $previous = $this->totals($prevFrom, $prevTo, $scope);

        $bestDay = collect($byDay)->sortByDesc('visits')->first();
        $daysWithTraffic = collect($byDay)->where('visits', '>', 0)->count();
        $days = max(count($byDay), 1);

        return [
            'visits' => $current['visits'],
            'unique_visitors' => $current['unique_visitors'],
            'visitor_days' => $current['visitor_days'],
            'pages_per_visitor' => $current['visitor_days'] > 0
                ? round($current['visitor_pageviews'] / $current['visitor_days'], 1)
                : null,
            'avg_daily_visits' => round($current['visits'] / $days, 1),
            'avg_daily_visitors' => round($current['visitor_days'] / $days, 1),
            'days_with_traffic' => $daysWithTraffic,
            'best_day' => $bestDay && $bestDay['visits'] > 0 ? $bestDay : null,
            'previous' => [
                'visits' => $previous['visits'],
                'unique_visitors' => $previous['unique_visitors'],
            ],
            'change' => [
                'visits' => $this->percentChange($previous['visits'], $current['visits']),
                'unique_visitors' => $this->percentChange($previous['unique_visitors'], $current['unique_visitors']),
            ],
        ];
    }

    /**
     * Visitas y visitantes por ámbito (sitio, talleres, app) en el rango.
     *
     * @return list<array{scope: string, visits: int, unique_visitors: int, share: float}>
     */
    public function byScope(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $visits = $this->betweenDates(PageVisit::query(), $from, $to)
            ->select('scope', DB::raw('sum(visits) as total'))
            ->groupBy('scope')
            ->pluck('total', 'scope');

        $visitors = $this->betweenDates(PageVisitVisitor::query(), $from, $to)
            ->select('scope', DB::raw('count(distinct visitor_hash) as total'))
            ->groupBy('scope')
            ->pluck('total', 'scope');

        $grand = max((int) $visits->sum(), 1);

        return array_map(fn (string $scope) => [
            'scope' => $scope,
            'visits' => (int) ($visits[$scope] ?? 0),
            'unique_visitors' => (int) ($visitors[$scope] ?? 0),
            'share' => round((int) ($visits[$scope] ?? 0) / $grand * 100, 1),
        ], PageVisit::SCOPES);
    }

    /**
     * @return list<array{path: string, scope: string, visits: int, unique_visits: int, share: float}>
     */
    public function topPages(CarbonImmutable $from, CarbonImmutable $to, string $scope, int $limit = 15): array
    {
        $rows = $this->visitsQuery($from, $to, $scope)
            ->select('path', 'scope', DB::raw('sum(visits) as total'), DB::raw('sum(unique_visits) as unique_total'))
            ->groupBy('path', 'scope')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        $grand = max((int) $this->visitsQuery($from, $to, $scope)->sum('visits'), 1);

        return $rows->map(fn ($row) => [
            'path' => '/'.ltrim((string) $row->path, '/'),
            'scope' => (string) $row->scope,
            'visits' => (int) $row->total,
            'unique_visits' => (int) $row->unique_total,
            'share' => round((int) $row->total / $grand * 100, 1),
        ])->values()->all();
    }

    /**
     * Páginas de entrada (primera página que ve cada visitante en el día).
     *
     * @return list<array{path: string, visitors: int}>
     */
    public function entryPages(CarbonImmutable $from, CarbonImmutable $to, string $scope, int $limit = 10): array
    {
        return $this->visitorsQuery($from, $to, $scope)
            ->select('entry_path', DB::raw('count(*) as total'))
            ->groupBy('entry_path')
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'path' => '/'.ltrim((string) $row->entry_path, '/'),
                'visitors' => (int) $row->total,
            ])->values()->all();
    }

    /**
     * Fuentes de tráfico. "Directo" agrupa a quienes llegaron sin referer externo.
     *
     * @return list<array{source: string, visitors: int, page_views: int, share: float}>
     */
    public function referrers(CarbonImmutable $from, CarbonImmutable $to, string $scope, int $limit = 10): array
    {
        $rows = $this->visitorsQuery($from, $to, $scope)
            ->select(
                DB::raw("coalesce(referrer, '') as source"),
                DB::raw('count(*) as visitors'),
                DB::raw('sum(page_views) as page_views'),
            )
            ->groupBy('source')
            ->orderByDesc('visitors')
            ->limit($limit)
            ->get();

        $grand = max((int) $rows->sum('visitors'), 1);

        return $rows->map(fn ($row) => [
            'source' => $row->source === '' ? 'Directo / desconocido' : (string) $row->source,
            'visitors' => (int) $row->visitors,
            'page_views' => (int) $row->page_views,
            'share' => round((int) $row->visitors / $grand * 100, 1),
        ])->values()->all();
    }

    /**
     * @return list<array{device: string, visitors: int, share: float}>
     */
    public function devices(CarbonImmutable $from, CarbonImmutable $to, string $scope): array
    {
        $rows = $this->visitorsQuery($from, $to, $scope)
            ->select('device', DB::raw('count(*) as total'))
            ->groupBy('device')
            ->pluck('total', 'device');

        $grand = max((int) $rows->sum(), 1);

        return collect(['desktop', 'mobile', 'tablet'])
            ->map(fn (string $device) => [
                'device' => $device,
                'visitors' => (int) ($rows[$device] ?? 0),
                'share' => round((int) ($rows[$device] ?? 0) / $grand * 100, 1),
            ])->values()->all();
    }

    /**
     * Promedio de visitas por día de la semana (lunes = 1).
     *
     * @param  list<array{date: string, visits: int, unique_visitors: int|null}>  $byDay
     * @return list<array{weekday: int, avg_visits: float, total_visits: int}>
     */
    public function byWeekday(array $byDay): array
    {
        $buckets = array_fill(1, 7, ['total' => 0, 'days' => 0]);

        foreach ($byDay as $row) {
            $weekday = CarbonImmutable::parse($row['date'])->isoWeekday();
            $buckets[$weekday]['total'] += $row['visits'];
            $buckets[$weekday]['days']++;
        }

        $result = [];
        foreach ($buckets as $weekday => $bucket) {
            $result[] = [
                'weekday' => $weekday,
                'total_visits' => $bucket['total'],
                'avg_visits' => $bucket['days'] > 0 ? round($bucket['total'] / $bucket['days'], 1) : 0.0,
            ];
        }

        return $result;
    }

    /**
     * Tráfico público por taller (landing, checkout, cotización pública).
     *
     * @return list<array{id: int|null, name: string, slug: string|null, visits: int, unique_visitors: int}>
     */
    public function byTenant(CarbonImmutable $from, CarbonImmutable $to, int $limit = 10): array
    {
        $visits = $this->betweenDates(PageVisit::query(), $from, $to)
            ->where('scope', PageVisit::SCOPE_TENANT)
            ->whereNotNull('tenant_id')
            ->select('tenant_id', DB::raw('sum(visits) as total'))
            ->groupBy('tenant_id')
            ->orderByDesc('total')
            ->limit($limit)
            ->pluck('total', 'tenant_id');

        if ($visits->isEmpty()) {
            return [];
        }

        $visitors = $this->betweenDates(PageVisitVisitor::query(), $from, $to)
            ->where('scope', PageVisit::SCOPE_TENANT)
            ->whereIn('tenant_id', $visits->keys())
            ->select('tenant_id', DB::raw('count(distinct visitor_hash) as total'))
            ->groupBy('tenant_id')
            ->pluck('total', 'tenant_id');

        $tenants = Tenant::query()->whereIn('id', $visits->keys())->get(['id', 'name', 'slug'])->keyBy('id');

        return $visits->map(fn ($total, $tenantId) => [
            'id' => (int) $tenantId,
            'name' => $tenants[$tenantId]->name ?? 'Taller eliminado',
            'slug' => $tenants[$tenantId]->slug ?? null,
            'visits' => (int) $total,
            'unique_visitors' => (int) ($visitors[$tenantId] ?? 0),
        ])->values()->all();
    }

    /**
     * Fecha desde la que existen datos de visitantes únicos (null si aún no hay).
     */
    public function trackingSince(): ?string
    {
        if (! $this->trackingSinceLoaded) {
            $min = PageVisitVisitor::query()->min('date');
            $this->trackingSince = $min ? $this->dateKey($min) : null;
            $this->trackingSinceLoaded = true;
        }

        return $this->trackingSince;
    }

    /**
     * @return array{visits: int, unique_visitors: int, visitor_days: int, visitor_pageviews: int}
     */
    private function totals(CarbonImmutable $from, CarbonImmutable $to, string $scope): array
    {
        $visitors = $this->visitorsQuery($from, $to, $scope)
            ->selectRaw('count(distinct visitor_hash) as unique_visitors, count(*) as visitor_days, coalesce(sum(page_views), 0) as pageviews')
            ->first();

        return [
            'visits' => (int) $this->visitsQuery($from, $to, $scope)->sum('visits'),
            'unique_visitors' => (int) ($visitors->unique_visitors ?? 0),
            'visitor_days' => (int) ($visitors->visitor_days ?? 0),
            'visitor_pageviews' => (int) ($visitors->pageviews ?? 0),
        ];
    }

    private function visitsQuery(CarbonImmutable $from, CarbonImmutable $to, string $scope): Builder
    {
        return $this->betweenDates(PageVisit::query(), $from, $to)
            ->when($scope !== self::SCOPE_ALL, fn (Builder $q) => $q->where('scope', $scope));
    }

    private function visitorsQuery(CarbonImmutable $from, CarbonImmutable $to, string $scope): Builder
    {
        return $this->betweenDates(PageVisitVisitor::query(), $from, $to)
            ->when($scope !== self::SCOPE_ALL, fn (Builder $q) => $q->where('scope', $scope));
    }

    /**
     * Rango de fechas inclusivo, compatible con columnas DATE (MySQL) y texto con hora (SQLite).
     */
    private function betweenDates(Builder $query, CarbonImmutable $from, CarbonImmutable $to): Builder
    {
        return $query
            ->where('date', '>=', $from->toDateString())
            ->where('date', '<', $to->addDay()->toDateString());
    }

    private function percentChange(int $previous, int $current): ?float
    {
        if ($previous === 0) {
            return $current > 0 ? null : 0.0;
        }

        return round(($current - $previous) / $previous * 100, 1);
    }

    private function dateKey(mixed $date): string
    {
        return substr((string) $date, 0, 10);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicBookingController extends Controller
{
    public function show(Request $request, Tenant $tenantBySlug): Response
    {
        $defaultDescription = $tenantBySlug->seo_description
            ?: "Agenda tu cita en {$tenantBySlug->name}. Diagnóstico rápido, repuestos garantizados y transparencia total.";
        $canonicalUrl = $request->url();

        return Inertia::render('Public/TenantLanding', [
            'tenant' => [
                'id' => $tenantBySlug->id,
                'name' => $tenantBySlug->name,
                'slug' => $tenantBySlug->slug,
                'domain' => $tenantBySlug->domain,
                'rut_taller' => $tenantBySlug->rut_taller,
                'plan' => $tenantBySlug->currentPlan()->value,
                'plan_label' => $tenantBySlug->currentPlan()->label(),
                'seo_description' => $tenantBySlug->seo_description,
                'seo_address' => $tenantBySlug->seo_address,
                'whatsapp_number' => $tenantBySlug->whatsapp_number,
            ],
            'seo' => [
                'title' => "Agendar Cita | {$tenantBySlug->name}",
                'description' => $defaultDescription,
                'canonical_url' => $canonicalUrl,
                'og_image' => $this->resolveSocialImageUrl(),
                'schema' => $this->resolveTenantLandingSchema($tenantBySlug, $canonicalUrl, $defaultDescription),
            ],
        ]);
    }

    public function store(Request $request, Tenant $tenantBySlug): RedirectResponse
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'plate' => ['required', 'string', 'size:6', 'regex:/^[A-Z0-9]+$/'],
            'appointment_date' => ['required', 'date', 'after:now'],
            'pre_check_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // Verificar disponibilidad: no permitir citas dentro de ±30 min del mismo taller
        $date = new Carbon($validated['appointment_date']);
        $conflict = Appointment::where('tenant_id', $tenantBySlug->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereBetween('appointment_date', [
                $date->copy()->subMinutes(30),
                $date->copy()->addMinutes(30),
            ])
            ->exists();

        if ($conflict) {
            return back()
                ->withErrors(['appointment_date' => 'Ese horario ya está reservado. Por favor elige otro con al menos 30 minutos de diferencia.'])
                ->withInput();
        }

        Appointment::create([
            'tenant_id' => $tenantBySlug->id,
            'plate' => strtoupper($validated['plate']),
            'customer_name' => $validated['customer_name'],
            'phone' => $validated['phone'],
            'appointment_date' => $validated['appointment_date'],
            'pre_check_notes' => $validated['pre_check_notes'] ?? null,
            'status' => 'pending',
        ]);

        return back()->with('booking_success', true);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function resolveTenantLandingSchema(Tenant $tenant, string $canonicalUrl, string $description): array
    {
        $businessSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'AutoRepair',
            '@id' => "{$canonicalUrl}#business",
            'name' => $tenant->name,
            'url' => $canonicalUrl,
            'description' => $description,
            'image' => $this->resolveSocialImageUrl(),
            'areaServed' => [
                '@type' => 'Country',
                'name' => 'Chile',
            ],
        ];

        if (filled($tenant->seo_address)) {
            $businessSchema['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => $tenant->seo_address,
                'addressCountry' => 'CL',
            ];
        }

        if (filled($tenant->whatsapp_number)) {
            $businessSchema['telephone'] = $tenant->whatsapp_number;
        }

        $businessSchema['potentialAction'] = [
            '@type' => 'ReserveAction',
            'target' => [
                '@type' => 'EntryPoint',
                'urlTemplate' => $canonicalUrl,
                'inLanguage' => 'es-CL',
                'actionPlatform' => [
                    'https://schema.org/DesktopWebPlatform',
                    'https://schema.org/MobileWebPlatform',
                ],
            ],
            'result' => [
                '@type' => 'Reservation',
                'name' => 'Reserva de Cita en Taller',
            ],
        ];

        return [
            $businessSchema,
            [
                '@context' => 'https://schema.org',
                '@type' => 'WebPage',
                '@id' => "{$canonicalUrl}#webpage",
                'url' => $canonicalUrl,
                'name' => "Agendar Cita | {$tenant->name}",
                'description' => $description,
                'about' => [
                    '@id' => "{$canonicalUrl}#business",
                ],
                'primaryImageOfPage' => [
                    '@type' => 'ImageObject',
                    'url' => $this->resolveSocialImageUrl(),
                ],
                'isPartOf' => [
                    '@type' => 'WebSite',
                    '@id' => url('/').'#website',
                    'url' => url('/'),
                    'name' => config('app.name', 'TallerFlow'),
                ],
            ],
        ];
    }
}

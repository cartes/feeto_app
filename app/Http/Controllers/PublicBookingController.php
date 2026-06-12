<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Mail\AppointmentScheduledMail;
use App\Mail\TenantContactMail;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Tenant;
use App\Models\TenantLead;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class PublicBookingController extends Controller
{
    public function show(Request $request, Tenant $tenantBySlug): Response
    {
        $defaultDescription = $tenantBySlug->seo_description
            ?: "Agenda tu cita en {$tenantBySlug->name}. Diagnóstico rápido, repuestos garantizados y transparencia total.";
        $canonicalUrl = $request->url();

        $branches = $tenantBySlug->branches()
            ->where('is_active', true)
            ->select(['id', 'name', 'address', 'phone', 'is_main'])
            ->orderByDesc('is_main')
            ->orderBy('name')
            ->get();

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
                'comuna' => $tenantBySlug->comuna,
                'whatsapp_number' => $tenantBySlug->whatsapp_number,
                'website_url' => $tenantBySlug->website_url,
                'primary_color' => $tenantBySlug->primary_color,
                'logo_url' => $tenantBySlug->logoUrl(),
                'branches' => $branches->map(fn ($b) => [
                    'id' => $b->id,
                    'name' => $b->name,
                    'address' => $b->address,
                    'phone' => $b->phone,
                    'is_main' => $b->is_main,
                ])->values(),
            ],
            'seo' => [
                'title' => "Agendamiento - {$tenantBySlug->name}",
                'description' => $defaultDescription,
                'canonical_url' => $canonicalUrl,
                'og_image' => $this->resolveSocialImageUrl(),
                'schema' => $this->resolveTenantLandingSchema($tenantBySlug, $branches, $canonicalUrl, $defaultDescription),
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

        $appointment = Appointment::create([
            'tenant_id' => $tenantBySlug->id,
            'plate' => strtoupper($validated['plate']),
            'customer_name' => $validated['customer_name'],
            'phone' => $validated['phone'],
            'appointment_date' => $validated['appointment_date'],
            'pre_check_notes' => $validated['pre_check_notes'] ?? null,
            'status' => 'pending',
        ]);

        $recipientEmail = $tenantBySlug->getNotificationEmail();
        Mail::to($recipientEmail)->send(new AppointmentScheduledMail($appointment));

        return back()->with('booking_success', true);
    }

    /**
     * @param  Collection<int, Branch>  $branches
     * @return array<int, array<string, mixed>>
     */
    private function resolveTenantLandingSchema(Tenant $tenant, Collection $branches, string $canonicalUrl, string $description): array
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

        if (filled($tenant->website_url)) {
            $businessSchema['sameAs'] = [$tenant->website_url];
        }

        if ($branches->isNotEmpty()) {
            $locations = $branches
                ->filter(fn (Branch $b): bool => filled($b->address) || filled($b->phone))
                ->map(function (Branch $b) use ($canonicalUrl): array {
                    $loc = [
                        '@type' => 'AutoRepair',
                        'name' => $b->name,
                        'url' => $canonicalUrl,
                    ];
                    if (filled($b->address)) {
                        $loc['address'] = [
                            '@type' => 'PostalAddress',
                            'streetAddress' => $b->address,
                            'addressCountry' => 'CL',
                        ];
                    }
                    if (filled($b->phone)) {
                        $loc['telephone'] = $b->phone;
                    }

                    return $loc;
                })
                ->values()
                ->all();

            if (! empty($locations)) {
                $businessSchema['location'] = count($locations) === 1 ? $locations[0] : $locations;
            }
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
                'name' => "Agendamiento - {$tenant->name}",
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

    public function storeContact(Request $request, Tenant $tenantBySlug): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'type' => ['required', 'string', 'in:general,quote'],
            'message' => ['required', 'string', 'max:2000'],
            'plate' => ['nullable', 'required_if:type,quote', 'string', 'size:6', 'regex:/^[A-Z0-9]+$/'],
            'brand' => ['nullable', 'required_if:type,quote', 'string', 'max:100'],
            'model' => ['nullable', 'required_if:type,quote', 'string', 'max:100'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:'.(date('Y') + 1)],
        ]);

        if (isset($validated['plate'])) {
            $validated['plate'] = strtoupper($validated['plate']);
        }

        $recipientEmail = $tenantBySlug->getNotificationEmail();

        TenantLead::create([
            'tenant_id' => $tenantBySlug->id,
            'source' => $validated['type'] === 'quote'
                ? TenantLead::SOURCE_CONTACT_QUOTE
                : TenantLead::SOURCE_CONTACT_GENERAL,
            'channel' => 'landing_page',
            'visitor_name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'metadata' => [
                'type' => $validated['type'],
                'message' => $validated['message'],
                'plate' => $validated['plate'] ?? null,
                'brand' => $validated['brand'] ?? null,
                'model' => $validated['model'] ?? null,
                'year' => $validated['year'] ?? null,
                'landing_path' => $request->path() ?: '/',
                'referer' => $request->headers->get('referer'),
            ],
            'occurred_at' => now(),
        ]);

        Mail::to($recipientEmail)->send(new TenantContactMail($validated));

        return back()->with('contact_success', true);
    }
}

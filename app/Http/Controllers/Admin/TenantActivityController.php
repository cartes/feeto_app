<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SendTenantDirectEmailRequest;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\EmailTracking;
use App\Models\LoginLog;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Quote;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkOrder;
use App\Notifications\TenantDirectEmailNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TenantActivityController extends Controller
{
    public function show(Request $request, Tenant $tenant): Response
    {
        $now = now();
        $thirtyDaysAgo = $now->copy()->subDays(30);

        // Stats generales del tenant
        $stats = [
            'users_count' => User::where('tenant_id', $tenant->id)->count(),
            'clients_count' => Client::where('tenant_id', $tenant->id)->count(),
            'work_orders_total' => WorkOrder::where('tenant_id', $tenant->id)->count(),
            'work_orders_30d' => WorkOrder::where('tenant_id', $tenant->id)
                ->where('created_at', '>=', $thirtyDaysAgo)->count(),
            'quotes_total' => Quote::where('tenant_id', $tenant->id)->count(),
            'quotes_accepted' => Quote::where('tenant_id', $tenant->id)
                ->where('status', Quote::STATUS_ACCEPTED)->count(),
            'products_count' => Product::where('tenant_id', $tenant->id)->count(),
            'appointments_total' => Appointment::where('tenant_id', $tenant->id)->count(),
            'last_login_at' => LoginLog::where('tenant_id', $tenant->id)
                ->latest('created_at')->value('created_at'),
            'logins_30d' => LoginLog::where('tenant_id', $tenant->id)
                ->where('created_at', '>=', $thirtyDaysAgo)->count(),
            'revenue_total' => Payment::where('tenant_id', $tenant->id)
                ->where('status', 'approved')->sum('amount'),
        ];

        // Órdenes de trabajo por día (últimos 30 días)
        $workOrdersByDay = WorkOrder::where('tenant_id', $tenant->id)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($r) => ['date' => $r->date, 'total' => (int) $r->total]);

        // Distribución de estados de OT
        $workOrdersByStatus = WorkOrder::where('tenant_id', $tenant->id)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->map(fn ($r) => ['status' => $r->status, 'total' => (int) $r->total]);

        // Logins por día (últimos 30 días)
        $loginsByDay = LoginLog::where('tenant_id', $tenant->id)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($r) => ['date' => $r->date, 'total' => (int) $r->total]);

        // Distribución de cotizaciones por estado
        $quotesByStatus = Quote::where('tenant_id', $tenant->id)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->map(fn ($r) => ['status' => $r->status, 'total' => (int) $r->total]);

        // Pagos del tenant
        $payments = Payment::where('tenant_id', $tenant->id)
            ->latest('created_at')
            ->limit(20)
            ->get(['id', 'amount', 'currency', 'billing_period', 'status', 'paid_at', 'created_at']);

        // Usuarios del tenant
        $users = User::where('tenant_id', $tenant->id)
            ->select(['id', 'name', 'email', 'created_at'])
            ->addSelect([
                'login_count' => LoginLog::selectRaw('count(*)')
                    ->whereColumn('user_id', 'users.id')
                    ->where('tenant_id', $tenant->id),
                'last_login_at' => LoginLog::select('created_at')
                    ->whereColumn('user_id', 'users.id')
                    ->where('tenant_id', $tenant->id)
                    ->latest()
                    ->limit(1),
            ])
            ->get();

        // Clientes del tenant (últimos 20)
        $clients = Client::where('tenant_id', $tenant->id)
            ->withCount('vehicles')
            ->latest()
            ->limit(20)
            ->get(['id', 'name', 'rut', 'phone', 'email', 'created_at']);

        // Órdenes de trabajo recientes
        $workOrders = WorkOrder::where('tenant_id', $tenant->id)
            ->with(['vehicle:id,plate'])
            ->latest()
            ->limit(20)
            ->get(['id', 'vehicle_id', 'status', 'total_amount', 'created_at']);

        // Cotizaciones recientes
        $quotes = Quote::where('tenant_id', $tenant->id)
            ->with(['client:id,name'])
            ->latest()
            ->limit(20)
            ->get(['id', 'client_id', 'status', 'subtotal_amount', 'created_at', 'sent_at']);

        // Inventario (productos con bajo stock primero)
        $products = Product::where('tenant_id', $tenant->id)
            ->orderByRaw('(physical_stock <= min_stock) DESC')
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'type', 'sku', 'selling_price', 'physical_stock', 'min_stock']);

        // Citas recientes
        $appointments = Appointment::where('tenant_id', $tenant->id)
            ->latest('appointment_date')
            ->limit(20)
            ->get(['id', 'customer_name', 'phone', 'plate', 'appointment_date', 'status']);

        // Logins recientes
        $recentLogins = LoginLog::where('tenant_id', $tenant->id)
            ->with('user:id,name,email')
            ->latest()
            ->limit(30)
            ->get(['id', 'user_id', 'ip', 'created_at']);

        // Historial de comunicaciones (correos enviados al taller con tracking)
        $emailTrackings = EmailTracking::where('tenant_id', $tenant->id)
            ->with('user:id,name,email')
            ->latest('sent_at')
            ->limit(50)
            ->get([
                'id',
                'user_id',
                'type',
                'recipient_email',
                'subject',
                'sent_at',
                'opened_at',
                'open_count',
                'clicked_at',
                'click_count',
                'offer_discount_percent',
                'metadata',
            ]);

        $publicUrl = route('taller.landing', ['tenantBySlug' => $tenant->slug]);
        $dashboardUrl = route('taller.dashboard', ['tenantBySlug' => $tenant->slug]);

        return Inertia::render('Admin/Tenants/Activity', [
            'tenant' => [
                ...$tenant->only(['id', 'name', 'slug', 'domain', 'plan', 'status', 'is_active', 'subscription_ends_at', 'rut_taller']),
                'public_url' => $publicUrl,
                'dashboard_url' => $dashboardUrl,
            ],
            'stats' => $stats,
            'charts' => [
                'work_orders_by_day' => $workOrdersByDay,
                'work_orders_by_status' => $workOrdersByStatus,
                'logins_by_day' => $loginsByDay,
                'quotes_by_status' => $quotesByStatus,
            ],
            'users' => $users,
            'clients' => $clients,
            'work_orders' => $workOrders,
            'quotes' => $quotes,
            'products' => $products,
            'appointments' => $appointments,
            'recent_logins' => $recentLogins,
            'payments' => $payments,
            'email_trackings' => $emailTrackings,
            'quick_templates' => $this->quickTemplates($dashboardUrl, $publicUrl),
        ]);
    }

    /**
     * Envía un correo directo desde el Super Admin hacia uno o todos los usuarios del taller.
     */
    public function sendEmail(SendTenantDirectEmailRequest $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validated();
        $recipients = $this->resolveRecipients($tenant, $validated['recipient_mode'], isset($validated['user_id']) ? (int) $validated['user_id'] : null);

        if ($recipients->isEmpty()) {
            return back()->with('error', "No se encontraron destinatarios válidos con correo electrónico en '{$tenant->name}'.");
        }

        $senderName = $request->user()?->name ?? 'Equipo Taller Flow';
        $publicUrl = route('taller.landing', ['tenantBySlug' => $tenant->slug]);
        $dashboardUrl = route('taller.dashboard', ['tenantBySlug' => $tenant->slug]);

        $sentEmails = [];

        foreach ($recipients as $recipient) {
            if (blank($recipient->email)) {
                continue;
            }

            $subject = $this->interpolateVariables(
                (string) $validated['subject'],
                $tenant,
                $recipient,
                $dashboardUrl,
                $publicUrl,
            );

            $body = $this->interpolateVariables(
                (string) $validated['body'],
                $tenant,
                $recipient,
                $dashboardUrl,
                $publicUrl,
            );

            $actionLabel = ! empty($validated['action_label'])
                ? $this->interpolateVariables((string) $validated['action_label'], $tenant, $recipient, $dashboardUrl, $publicUrl)
                : null;

            $actionUrl = ! empty($validated['action_url'])
                ? trim((string) $validated['action_url'])
                : null;

            $tracking = EmailTracking::create([
                'tenant_id' => $tenant->id,
                'user_id' => $recipient->id,
                'type' => 'admin_direct',
                'recipient_email' => $recipient->email,
                'token' => Str::random(40),
                'subject' => $subject,
                'metadata' => [
                    'body' => $body,
                    'template_key' => $validated['template_key'] ?? 'custom',
                    'action_label' => $actionLabel,
                    'action_url' => $actionUrl,
                    'recipient_mode' => $validated['recipient_mode'],
                    'sent_by' => $senderName,
                ],
                'sent_at' => now(),
            ]);

            $recipient->notify(new TenantDirectEmailNotification(
                tenant: $tenant,
                emailSubject: $subject,
                emailBody: $body,
                trackingToken: $tracking->token,
                actionLabel: $actionLabel,
                actionUrl: $actionUrl,
                senderName: $senderName,
            ));

            $sentEmails[] = $recipient->email;
        }

        if ($sentEmails === []) {
            return back()->with('error', 'Ninguno de los destinatarios seleccionados cuenta con un correo electrónico válido.');
        }

        AuditLog::record(
            'tenant.direct_email_sent',
            'Correo directo enviado a '.implode(', ', $sentEmails)." en '{$tenant->name}': {$validated['subject']}",
            $tenant
        );

        $count = count($sentEmails);
        $message = $count === 1
            ? "Correo enviado exitosamente a {$sentEmails[0]}."
            : "Correo enviado exitosamente a {$count} usuarios del taller.";

        return back()->with('success', $message);
    }

    /**
     * @return Collection<int, User>
     */
    private function resolveRecipients(Tenant $tenant, string $mode, ?int $userId): Collection
    {
        if ($mode === 'user' && $userId !== null) {
            return User::where('tenant_id', $tenant->id)
                ->where('id', $userId)
                ->get();
        }

        if ($mode === 'all') {
            return User::where('tenant_id', $tenant->id)
                ->whereNotNull('email')
                ->orderBy('id')
                ->get();
        }

        $tenant->makeCurrent();

        $admin = User::where('tenant_id', $tenant->id)
            ->whereHas('roles', fn ($q) => $q->where('name', 'Admin'))
            ->first() ?? User::where('tenant_id', $tenant->id)->orderBy('id')->first();

        Tenant::forgetCurrent();

        return $admin ? collect([$admin]) : collect();
    }

    private function interpolateVariables(
        string $text,
        Tenant $tenant,
        User $recipient,
        string $dashboardUrl,
        string $publicUrl,
    ): string {
        return strtr($text, [
            '{nombre_taller}' => (string) $tenant->name,
            '{nombre_usuario}' => (string) $recipient->name,
            '{email_usuario}' => (string) $recipient->email,
            '{link_panel}' => $dashboardUrl,
            '{link_publico}' => $publicUrl,
        ]);
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function quickTemplates(string $dashboardUrl, string $publicUrl): array
    {
        return [
            [
                'key' => 'onboarding',
                'label' => 'Bienvenida y acompañamiento',
                'subject' => '[Taller Flow] Hola {nombre_usuario}, ¿te ayudamos a configurar {nombre_taller}?',
                'body' => "Hola {nombre_usuario},\n\nVimos que ya tienes activa la cuenta de {nombre_taller} en Taller Flow y queríamos saber cómo va todo con la configuración inicial.\n\nRecuerda que tu taller ya tiene habilitada su página pública de agendamiento en:\n{link_publico}\n\nSi necesitas ayuda para cargar tu inventario, configurar tus servicios o capacitar a tu equipo, responde directamente este correo y coordinamos una llamada rápida.",
                'action_label' => 'Ir al panel de mi taller',
                'action_url' => $dashboardUrl,
            ],
            [
                'key' => 'low_activity',
                'label' => 'Seguimiento por baja actividad',
                'subject' => '[Taller Flow] Te extrañamos en {nombre_taller} — ¿necesitas ayuda con el sistema?',
                'body' => "Hola {nombre_usuario},\n\nNotamos que en los últimos días no han registrado nuevas órdenes de trabajo en {nombre_taller} y queremos asegurarnos de que no hayas tenido ningún inconveniente técnico.\n\n¿Hay alguna función o flujo que te gustaría ajustar a la medida de tu taller? Responde este correo con confianza y te ayudamos paso a paso.",
                'action_label' => 'Ingresar a mi taller',
                'action_url' => $dashboardUrl,
            ],
            [
                'key' => 'support_checkin',
                'label' => 'Contacto de soporte / Feedback',
                'subject' => '[Taller Flow] Seguimiento y soporte para {nombre_taller}',
                'body' => "Hola {nombre_usuario},\n\nTe escribimos del equipo de Taller Flow para conocer tu experiencia usando la plataforma en {nombre_taller}.\n\nSi tienes cualquier consulta, sugerencia de mejora o necesitas asistencia con alguna orden o cotización, estamos a un mensaje de distancia respondiendo este correo.",
                'action_label' => 'Abrir Taller Flow',
                'action_url' => $dashboardUrl,
            ],
            [
                'key' => 'public_booking_tip',
                'label' => 'Tip: Enlace de agendamiento público',
                'subject' => '[Taller Flow] Comparte el link de agendamiento de {nombre_taller} con tus clientes',
                'body' => "Hola {nombre_usuario},\n\nUn consejo rápido para {nombre_taller}: puedes pegar tu enlace público de agendamiento en la biografía de tu Instagram o en el mensaje automático de WhatsApp Business:\n\n{link_publico}\n\nCon ese link tus clientes pueden reservar citas o pedir cotizaciones 24/7 y recibirás una alerta inmediata en tu panel.",
                'action_label' => 'Ver mi página pública',
                'action_url' => $publicUrl,
            ],
        ];
    }
}

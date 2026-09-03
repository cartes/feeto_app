<?php

use App\Http\Controllers\AcquisitionReportController;
use App\Http\Controllers\Admin\AdminProfileController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BlogCategoryController;
use App\Http\Controllers\Admin\BlogPostController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LandingPageSeoController;
use App\Http\Controllers\Admin\MediaFileController;
use App\Http\Controllers\Admin\MercadoPagoWebhookController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\TenantActivityController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\TrialRequestController as AdminTrialRequestController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SmartReceptionController;
use App\Http\Controllers\Api\WorkOrderItemController;
use App\Http\Controllers\Api\WorkOrderModalController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientInvoiceController;
use App\Http\Controllers\CollectionsReportController;
use App\Http\Controllers\CustomerReportController;
use App\Http\Controllers\InternalNoteController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InventoryReportController;
use App\Http\Controllers\ManualQuoteController;
use App\Http\Controllers\ManualQuoteTrackingController;
use App\Http\Controllers\OcrController;
use App\Http\Controllers\OnboardingTourController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicBlogController;
use App\Http\Controllers\PublicBookingController;
use App\Http\Controllers\PublicCheckoutController;
use App\Http\Controllers\PublicOrdenDeTrabajoController;
use App\Http\Controllers\PublicPricingController;
use App\Http\Controllers\PublicServicesController;
use App\Http\Controllers\PublicTenantDirectoryController;
use App\Http\Controllers\PublicWhatsAppInquiryController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\ReceptionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\SalesReportController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\Subscription\BillingController;
use App\Http\Controllers\Subscription\TenantSubscriptionController;
use App\Http\Controllers\SupervisorReportController;
use App\Http\Controllers\TallerDashboardController;
use App\Http\Controllers\TenantBrandingController;
use App\Http\Controllers\TenantNotificationController;
use App\Http\Controllers\TenantRoleController;
use App\Http\Controllers\TenantSchedulingController;
use App\Http\Controllers\TenantSeoController;
use App\Http\Controllers\TenantSettingsController;
use App\Http\Controllers\TenantUserController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\TrialRequestController;
use App\Http\Controllers\VehicleCatalogController;
use App\Http\Controllers\WelcomeController;
use App\Http\Controllers\WorkbookImportController;
use App\Http\Controllers\WorkOrderController;
use App\Http\Middleware\IsSuperAdmin;
use App\Http\Middleware\SetTenantRouteDefaults;
use App\Models\Tenant;
use App\Services\Reports\ReportDataService;
use App\Services\TenantSetupService;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Spatie\Multitenancy\Http\Middleware\NeedsTenant;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/*
|--------------------------------------------------------------------------
| Rutas Públicas — Visibles para cualquier visitante (clientes del taller)
|--------------------------------------------------------------------------
*/
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');

Route::get('/', WelcomeController::class)->name('home');
Route::get('/precios', PublicPricingController::class)->name('pricing');
Route::get('/servicios', PublicServicesController::class)->name('servicios');
Route::get('/orden-de-trabajo-taller-mecanico', PublicOrdenDeTrabajoController::class)->name('orden-de-trabajo-taller-mecanico');

// Blog Público
Route::get('/blog', [PublicBlogController::class, 'index'])->name('blog.index');
Route::get('/blog/categoria/{slug}', [PublicBlogController::class, 'category'])->name('blog.category');
Route::get('/blog/{slug}', [PublicBlogController::class, 'show'])->name('blog.show');

// Directorio público de talleres
Route::get('/talleres', [PublicTenantDirectoryController::class, 'index'])->name('talleres.index');

// Solicitud de prueba gratuita
Route::get('/trial', [TrialRequestController::class, 'create'])->name('trial.create');
Route::post('/trial', [TrialRequestController::class, 'store'])->middleware('throttle:5,1')->name('trial.store');
Route::get('/trial/gracias', [TrialRequestController::class, 'success'])->name('trial.success');

// Tracking de Orden de Trabajo (Público con rate limit)
Route::get('/ot/{uuid}', [TrackingController::class, 'show'])
    ->middleware('throttle:30,1')
    ->name('tracking.show');
Route::post('/ot/{uuid}/quote/respond', [QuoteController::class, 'respond'])
    ->middleware('throttle:20,1')
    ->name('tracking.quote.respond');

// Tracking de Cotización Manual (Público con rate limit)
Route::get('/cotizacion/{uuid}', [ManualQuoteTrackingController::class, 'show'])
    ->middleware('throttle:30,1')
    ->name('quotes.public.show');
Route::post('/cotizacion/{uuid}/respond', [ManualQuoteController::class, 'respond'])
    ->middleware('throttle:20,1')
    ->name('quotes.public.respond');

// Landing page pública del taller — embudo de conversión con Pre-Check ALPR
Route::get('/taller/{tenantBySlug}', [PublicBookingController::class, 'show'])->name('taller.landing');
Route::post('/taller/{tenantBySlug}/booking', [PublicBookingController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('taller.booking.store');
Route::post('/taller/{tenantBySlug}/contacto', [PublicBookingController::class, 'storeContact'])
    ->middleware('throttle:10,1')
    ->name('taller.contact.store');
Route::post('/taller/{tenantBySlug}/whatsapp-inquiry', [PublicWhatsAppInquiryController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('taller.whatsapp.inquiry');

/*
|--------------------------------------------------------------------------
| Rutas Privadas — Administración del taller (requieren autenticación)
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', function (Request $request): Response {
    $user = $request->user();

    if ($user->is_super_admin) {
        if ($request->header('X-Inertia')) {
            return Inertia::location(route('admin.dashboard'));
        }

        return redirect()->route('admin.dashboard');
    }

    if ($user->tenant) {
        if ($request->header('X-Inertia')) {
            return Inertia::location(route('taller.dashboard', ['tenantBySlug' => $user->tenant->slug]));
        }

        return redirect()->route('taller.dashboard', ['tenantBySlug' => $user->tenant->slug]);
    }

    abort(403, 'Este usuario no tiene un taller asignado.');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::post('/onboarding-tour/complete', OnboardingTourController::class)
    ->middleware(['auth', 'verified'])
    ->name('onboarding-tour.complete');

Route::middleware(['auth', 'verified', NeedsTenant::class, SetTenantRouteDefaults::class])
    ->prefix('/taller/{tenantBySlug}')
    ->group(function () {
        Route::get('/dashboard', TallerDashboardController::class)->name('taller.dashboard');

        // Nueva Recepción — Usuarios con permiso appointments.manage
        Route::get('/receptions/create', [ReceptionController::class, 'create'])
            ->middleware('permission:appointments.manage')
            ->name('receptions.create');
        Route::get('/vehicle-catalog/brands/{vehicleBrand}/models', [VehicleCatalogController::class, 'models'])
            ->name('vehicle-catalog.models');
        Route::post('/receptions', [ReceptionController::class, 'store'])
            ->middleware(['throttle:20,1', 'permission:appointments.manage', 'tenant.feature:ai_reception'])
            ->name('receptions.store');
        Route::post('/receptions/preview', [ReceptionController::class, 'preview'])
            ->middleware(['throttle:30,1', 'permission:appointments.manage'])
            ->name('receptions.preview');
        Route::get('/receptions/clients/search', [ReceptionController::class, 'searchClients'])
            ->middleware('permission:appointments.manage')
            ->name('receptions.clients.search');
        Route::post('/receptions/store-order', [ReceptionController::class, 'storeOrder'])
            ->middleware('permission:appointments.manage')
            ->name('receptions.store_order');

        // OCR de Patentes
        Route::post('/ocr/process', [OcrController::class, 'process'])
            ->middleware(['throttle:20,1', 'permission:appointments.manage', 'tenant.feature:ai_reception'])
            ->name('ocr.process');

        // Work Orders / Kanban — permisos granulares por acción
        Route::get('/work-orders', [WorkOrderController::class, 'index'])->name('work-orders.index');
        Route::get('/work-orders/{workOrder}', [WorkOrderController::class, 'show'])->name('work-orders.show');
        Route::put('/work-orders/{workOrder}/status', [WorkOrderController::class, 'updateStatus'])
            ->middleware('permission:work-orders.update-status')
            ->name('work-orders.status.update');
        Route::delete('/work-orders/{workOrder}', [WorkOrderController::class, 'destroy'])
            ->middleware('permission:work-orders.delete')
            ->name('work-orders.destroy');
        Route::post('/work-orders/{workOrder}/items', [WorkOrderController::class, 'addItem'])
            ->middleware('permission:work-orders.manage-items')
            ->name('work-orders.items.store');
        Route::delete('/work-orders/{workOrder}/items/{item}', [WorkOrderController::class, 'removeItem'])
            ->middleware('permission:work-orders.manage-items')
            ->name('work-orders.items.destroy');
        Route::post('/work-orders/{workOrder}/quote/send', [QuoteController::class, 'send'])
            ->middleware('permission:work-orders.view|work-orders.view-own')
            ->name('work-orders.quote.send');
        Route::post('/work-orders/{workOrder}/quote/notify-ready', [QuoteController::class, 'notifyReady'])
            ->middleware('permission:work-orders.view|work-orders.view-own')
            ->name('work-orders.quote.notify-ready');
        Route::post('/work-orders/{workOrder}/quote/approve-manually', [QuoteController::class, 'approveManually'])
            ->middleware('permission:work-orders.view|work-orders.view-own')
            ->name('work-orders.quote.approve-manually');
        Route::patch('/work-orders/{workOrder}/quote/tax', [QuoteController::class, 'updateTax'])
            ->middleware('permission:work-orders.manage-items')
            ->name('work-orders.quote.tax');

        // Cotizaciones manuales — independientes de OT/Cita
        Route::get('/quotes', [ManualQuoteController::class, 'index'])
            ->middleware('permission:work-orders.view|work-orders.view-own')
            ->name('quotes.index');
        Route::get('/quotes/create', [ManualQuoteController::class, 'create'])
            ->middleware('permission:work-orders.view|work-orders.view-own')
            ->name('quotes.create');
        Route::get('/quotes/search-plate', [ManualQuoteController::class, 'searchByPlate'])
            ->middleware('permission:work-orders.view|work-orders.view-own')
            ->name('quotes.search-plate');
        Route::post('/quotes/store-client', [ManualQuoteController::class, 'storeClientWithVehicle'])
            ->middleware('permission:work-orders.view|work-orders.view-own')
            ->name('quotes.store-client');
        Route::post('/quotes', [ManualQuoteController::class, 'store'])
            ->middleware('permission:work-orders.view|work-orders.view-own')
            ->name('quotes.store');
        Route::get('/quotes/{quote}', [ManualQuoteController::class, 'show'])
            ->middleware('permission:work-orders.view|work-orders.view-own')
            ->name('quotes.show');
        Route::post('/quotes/{quote}/items', [ManualQuoteController::class, 'addItem'])
            ->middleware('permission:work-orders.manage-items')
            ->name('quotes.items.store');
        Route::delete('/quotes/{quote}/items/{item}', [ManualQuoteController::class, 'removeItem'])
            ->middleware('permission:work-orders.manage-items')
            ->name('quotes.items.destroy');
        Route::patch('/quotes/{quote}/tax', [ManualQuoteController::class, 'updateTax'])
            ->middleware('permission:work-orders.manage-items')
            ->name('quotes.tax.update');
        Route::post('/quotes/{quote}/send', [ManualQuoteController::class, 'send'])
            ->middleware('permission:work-orders.view|work-orders.view-own')
            ->name('quotes.send');
        Route::post('/quotes/{quote}/approve-manually', [ManualQuoteController::class, 'approveManually'])
            ->middleware('permission:work-orders.view|work-orders.view-own')
            ->name('quotes.approve-manually');
        Route::get('/clients/{client}/vehicles', [ManualQuoteController::class, 'vehiclesForClient'])
            ->middleware('permission:work-orders.view|work-orders.view-own')
            ->name('clients.vehicles');

        // API Modals
        Route::get('/api/work-orders/{id}', [WorkOrderModalController::class, 'show'])->name('api.work-orders.show');
        Route::post('/api/work-orders/{id}/images', [WorkOrderModalController::class, 'uploadImage'])->name('api.work-orders.images.upload');
        Route::delete('/api/work-orders/images/{imageId}', [WorkOrderModalController::class, 'destroyImage'])->name('api.work-orders.images.destroy');

        Route::post('/api/work-orders/{workOrder}/items', [WorkOrderItemController::class, 'store'])
            ->middleware('permission:work-orders.manage-items')
            ->name('api.work-orders.items.store');
        Route::delete('/api/work-orders/{workOrder}/items/{item}', [WorkOrderItemController::class, 'destroy'])
            ->middleware('permission:work-orders.manage-items')
            ->name('api.work-orders.items.destroy');

        Route::get('/api/products', [ProductController::class, 'index'])->name('api.products.index');

        // Inventory — usuarios con permiso inventory.manage
        Route::resource('inventory', InventoryController::class)
            ->only(['index', 'show', 'store', 'update', 'destroy'])
            ->parameters(['inventory' => 'product'])
            ->middleware('permission:inventory.manage');

        Route::get('/inventory/import/template', [WorkbookImportController::class, 'downloadProductsTemplate'])
            ->middleware('permission:inventory.manage')
            ->name('inventory.import.template');
        Route::post('/inventory/import', [WorkbookImportController::class, 'importProducts'])
            ->middleware('permission:inventory.manage')
            ->name('inventory.import');

        Route::get('/inventory/{product}/movements', [InventoryController::class, 'movements'])
            ->middleware('permission:inventory.manage')
            ->name('inventory.movements');

        Route::resource('product-categories', ProductCategoryController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['product-categories' => 'productCategory'])
            ->middleware('permission:inventory.manage');

        Route::resource('services', ServiceController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['services' => 'service'])
            ->middleware('permission:inventory.manage');

        // Branches — usuarios con permiso branches.manage
        Route::resource('branches', BranchController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['branches' => 'branch'])
            ->middleware('permission:branches.manage');

        // Clients — usuarios con permiso customers.manage
        Route::resource('clients', ClientController::class)
            ->only(['index', 'show', 'store'])
            ->middleware('permission:customers.manage');

        Route::get('/clients/import/template', [WorkbookImportController::class, 'downloadClientsTemplate'])
            ->middleware('permission:customers.manage')
            ->name('clients.import.template');
        Route::post('/clients/import', [WorkbookImportController::class, 'importClients'])
            ->middleware('permission:customers.manage')
            ->name('clients.import');

        Route::post('clients/{client}/notes', [InternalNoteController::class, 'store'])
            ->middleware('permission:customers.manage')
            ->name('clients.notes.store');

        // Appointments & Smart Reception — usuarios con permiso appointments.manage
        Route::get('/appointments', [AppointmentController::class, 'index'])
            ->middleware('permission:appointments.manage')
            ->name('appointments.index');
        Route::delete('/appointments/{appointment}', [AppointmentController::class, 'destroy'])
            ->middleware('permission:appointments.manage')
            ->name('appointments.destroy');
        Route::post('/api/appointments/scan-plate', [SmartReceptionController::class, 'scanPlate'])
            ->middleware(['permission:appointments.manage', 'tenant.feature:ai_reception'])
            ->name('api.appointments.scan-plate');

        // Gestión de usuarios del taller — usuarios con permiso users.manage
        Route::get('/users', [TenantUserController::class, 'index'])
            ->middleware('permission:users.manage')
            ->name('tenant.users.index');
        Route::post('/users', [TenantUserController::class, 'store'])
            ->middleware('permission:users.manage')
            ->name('tenant.users.store');
        Route::delete('/users/{user}', [TenantUserController::class, 'destroy'])
            ->middleware('permission:users.manage')
            ->name('tenant.users.destroy');

        // Configuración del taller — usuarios con permiso users.manage
        Route::get('/settings', [TenantSettingsController::class, 'index'])
            ->middleware('permission:users.manage')
            ->name('taller.settings');
        Route::patch('/settings/commercial', [TenantSettingsController::class, 'updateCommercial'])
            ->middleware('permission:users.manage')
            ->name('taller.settings.commercial.update');
        Route::patch('/settings/seo', [TenantSeoController::class, 'update'])
            ->middleware('permission:users.manage')
            ->name('taller.settings.seo.update');
        Route::post('/settings/seo/generate-description', [TenantSeoController::class, 'generateDescription'])
            ->middleware(['permission:users.manage', 'throttle:10,1'])
            ->name('taller.settings.seo.generate');

        // Branding del taller — color y logo (solo Admin)
        Route::patch('/settings/branding/color', [TenantBrandingController::class, 'updateColor'])
            ->middleware('permission:users.manage')
            ->name('taller.settings.branding.color');
        Route::post('/settings/branding/logo', [TenantBrandingController::class, 'uploadLogo'])
            ->middleware('permission:users.manage')
            ->name('taller.settings.branding.logo');
        Route::delete('/settings/branding/logo', [TenantBrandingController::class, 'deleteLogo'])
            ->middleware('permission:users.manage')
            ->name('taller.settings.branding.logo.delete');

        Route::patch('/settings/scheduling', [TenantSchedulingController::class, 'update'])
            ->middleware('permission:users.manage')
            ->name('taller.settings.scheduling.update');
        Route::get('/settings/scheduling/feriados', [TenantSchedulingController::class, 'feriados'])
            ->middleware('permission:users.manage')
            ->name('taller.settings.scheduling.feriados');

        // Notificaciones CRM
        Route::prefix('/notifications')->name('notifications.')->group(function (): void {
            Route::get('/', [TenantNotificationController::class, 'index'])->name('index');
            Route::post('/{notification}/read', [TenantNotificationController::class, 'markRead'])->name('read');
            Route::post('/read-all', [TenantNotificationController::class, 'markAllRead'])->name('read-all');
        });

        // Gestión de roles — usuarios con permiso users.manage (gate custom_roles en controlador)
        Route::get('/settings/roles', [TenantRoleController::class, 'index'])
            ->middleware('permission:users.manage')
            ->name('taller.roles.index');
        Route::get('/settings/roles/create', [TenantRoleController::class, 'create'])
            ->middleware('permission:users.manage')
            ->name('taller.roles.create');
        Route::post('/settings/roles', [TenantRoleController::class, 'store'])
            ->middleware('permission:users.manage')
            ->name('taller.roles.store');
        Route::get('/settings/roles/{role}/edit', [TenantRoleController::class, 'edit'])
            ->middleware('permission:users.manage')
            ->name('taller.roles.edit');
        Route::put('/settings/roles/{role}', [TenantRoleController::class, 'update'])
            ->middleware('permission:users.manage')
            ->name('taller.roles.update');
        Route::delete('/settings/roles/{role}', [TenantRoleController::class, 'destroy'])
            ->middleware('permission:users.manage')
            ->name('taller.roles.destroy');

        Route::get('/reports', [ReportController::class, 'index'])
            ->middleware('permission:reports.view')
            ->name('reports.index');
        Route::get('/reports/ventas', [SalesReportController::class, 'index'])
            ->middleware('permission:reports.view')
            ->name('reports.sales');
        Route::get('/reports/supervisores', [SupervisorReportController::class, 'index'])
            ->middleware('permission:reports.view')
            ->name('reports.supervisors');
        Route::get('/reports/existencias', [InventoryReportController::class, 'index'])
            ->middleware('permission:reports.view')
            ->name('reports.inventory');
        Route::get('/reports/clientes', [CustomerReportController::class, 'index'])
            ->middleware('permission:reports.view')
            ->name('reports.customers');
        Route::get('/reports/cobranza', [CollectionsReportController::class, 'index'])
            ->middleware('permission:reports.view')
            ->name('reports.collections');
        Route::get('/reports/adquisicion', [AcquisitionReportController::class, 'index'])
            ->middleware('permission:reports.view')
            ->name('reports.acquisition');
        Route::get('/reports/export/{report}/pdf', [ReportExportController::class, 'pdf'])
            ->whereIn('report', ReportDataService::reportKeys())
            ->middleware('permission:reports.view')
            ->name('reports.export.pdf');
        Route::get('/reports/export/{report}/excel', [ReportExportController::class, 'excel'])
            ->whereIn('report', ReportDataService::reportKeys())
            ->middleware('permission:reports.view')
            ->name('reports.export.excel');

        Route::resource('invoices', ClientInvoiceController::class)
            ->only(['index', 'show', 'store'])
            ->parameters(['invoices' => 'clientInvoice'])
            ->middleware('permission:financials.view');

        // Perfil
        // Suscripción — autogestión del tenant
        Route::prefix('/subscription')->name('subscription.')->group(function (): void {
            Route::get('/planes', [TenantSubscriptionController::class, 'plans'])->name('plans');
            Route::post('/preference', [TenantSubscriptionController::class, 'createPreference'])->name('preference');
            Route::get('/exito', [TenantSubscriptionController::class, 'success'])->name('success');
            Route::get('/fallo', [TenantSubscriptionController::class, 'failure'])->name('failure');
            Route::get('/pendiente', [TenantSubscriptionController::class, 'pending'])->name('pending');
            Route::get('/billing', [BillingController::class, 'index'])->name('billing');
        });

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

        Route::get('/media/{path}', function (string $path) {
            $user = auth()->user();
            $tenant = Tenant::current();

            if (! $tenant || (! $user->is_super_admin && $user->tenant_id !== $tenant->id)) {
                abort(403, 'No tienes acceso a estos archivos.');
            }

            $tenantPrefix = "tenants/{$tenant->id}/";

            if (! str_starts_with($path, $tenantPrefix)) {
                abort(403, 'Acceso denegado a archivos de otros talleres.');
            }

            if (str_contains($path, '..')) {
                abort(400, 'Ruta inválida.');
            }

            $fullPath = storage_path('app/private/'.$path);

            if (! file_exists($fullPath)) {
                abort(404);
            }

            return response()->file($fullPath);
        })->where('path', '.*')->name('storage.serve');
    });

// Admin Route Group without NeedsTenant middleware
Route::middleware(['auth', 'verified', IsSuperAdmin::class])
    ->withoutMiddleware([NeedsTenant::class])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');

        // Perfil & API Keys
        Route::get('/profile', [AdminProfileController::class, 'edit'])->name('profile');
        Route::put('/profile', [AdminProfileController::class, 'updateProfile'])->name('profile.update');
        Route::put('/profile/password', [AdminProfileController::class, 'updatePassword'])->name('profile.password');
        Route::put('/profile/api-keys', [AdminProfileController::class, 'updateApiKeys'])->name('profile.api-keys');
        Route::put('/profile/analytics', [AdminProfileController::class, 'updateAnalytics'])->name('profile.analytics');

        // Planes
        Route::resource('/plans', PlanController::class)->except(['show']);

        // Tenants
        Route::get('/tenants', [TenantController::class, 'index'])->name('tenants.index');
        Route::get('/tenants/create', [TenantController::class, 'create'])->name('tenants.create');
        Route::post('/tenants', [TenantController::class, 'store'])->name('tenants.store');
        Route::get('/tenants/{tenant}/edit', [TenantController::class, 'edit'])->name('tenants.edit');
        Route::put('/tenants/{tenant}', [TenantController::class, 'update'])->name('tenants.update');
        Route::put('/tenants/{tenant}/admin', [TenantController::class, 'updateAdmin'])->name('tenants.update_admin');
        Route::put('/tenants/{tenant}/suspend', [TenantController::class, 'suspend'])->name('tenants.suspend');
        Route::get('/tenants/{tenant}/activity', [TenantActivityController::class, 'show'])->name('tenants.activity');

        // Usuarios
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::put('/users/{user}/password', [UserController::class, 'changePassword'])->name('users.change-password');

        // Audit Log
        Route::get('/audit', [AuditLogController::class, 'index'])->name('audit.index');

        // SEO páginas públicas
        Route::get('/landing-seo', [LandingPageSeoController::class, 'index'])->name('landing-seo.index');
        Route::put('/landing-seo', [LandingPageSeoController::class, 'update'])->name('landing-seo.update');
        Route::put('/landing-seo/whatsapp', [LandingPageSeoController::class, 'updateWhatsApp'])->name('landing-seo.whatsapp.update');
        Route::post('/landing-seo/{pageKey}/og-image', [LandingPageSeoController::class, 'uploadOgImage'])
            ->where('pageKey', 'home|pricing|trial|blog')
            ->name('landing-seo.og-image.upload');
        Route::delete('/landing-seo/{pageKey}/og-image', [LandingPageSeoController::class, 'deleteOgImage'])
            ->where('pageKey', 'home|pricing|trial|blog')
            ->name('landing-seo.og-image.delete');

        // Blog
        Route::resource('/blog', BlogPostController::class)->except(['show']);
        Route::resource('/blog-categories', BlogCategoryController::class)->except(['show']);
        Route::resource('/media-library', MediaFileController::class)->except(['show', 'create', 'edit'])->parameters(['media-library' => 'mediaFile']);

        // Solicitudes de prueba gratuita
        Route::get('/trial-requests', [AdminTrialRequestController::class, 'index'])->name('trial-requests.index');
        Route::post('/trial-requests/{trialRequest}/approve', [AdminTrialRequestController::class, 'approve'])->name('trial-requests.approve');
        Route::post('/trial-requests/{trialRequest}/reject', [AdminTrialRequestController::class, 'reject'])->name('trial-requests.reject');
        Route::delete('/trial-requests/{trialRequest}', [AdminTrialRequestController::class, 'destroy'])->name('trial-requests.destroy');

        // Pagos
        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::post('/payments/preference', [PaymentController::class, 'createPreference'])->name('payments.preference');
        Route::get('/payments/success', [PaymentController::class, 'success'])->name('payments.success');
        Route::get('/payments/failure', [PaymentController::class, 'failure'])->name('payments.failure');
        Route::get('/payments/pending', [PaymentController::class, 'pending'])->name('payments.pending');
    });

// Checkout público por slug — permite comprar suscripción sin autenticación
Route::prefix('/checkout/{tenantBySlug}')->name('checkout.')->group(function (): void {
    Route::get('/', [PublicCheckoutController::class, 'show'])->name('show');
    Route::post('/preference', [PublicCheckoutController::class, 'createPreference'])
        ->middleware('throttle:10,1')
        ->name('preference');
    Route::get('/exito', [PublicCheckoutController::class, 'success'])->name('success');
    Route::get('/fallo', [PublicCheckoutController::class, 'failure'])->name('failure');
    Route::get('/pendiente', [PublicCheckoutController::class, 'pending'])->name('pending');
});

// Mercado Pago webhook (sin CSRF, sin auth — validado por firma HMAC)
Route::post('/webhooks/mercadopago', MercadoPagoWebhookController::class)
    ->name('admin.payments.webhook')
    ->withoutMiddleware([PreventRequestForgery::class]);

require __DIR__.'/auth.php';

// *** RUTA TEMPORAL DE DIAGNÓSTICO — eliminar en producción ***
if (config('app.env') === 'local') {
    Route::get('/debug/roles', function () {
        $results = [];

        $tenants = Tenant::with('users.roles')->get();

        foreach ($tenants as $tenant) {
            $tenant->makeCurrent();
            $tenantData = [
                'tenant' => $tenant->name,
                'slug' => $tenant->slug,
                'roles_en_bd' => Role::pluck('name'),
                'users' => [],
            ];

            foreach ($tenant->users as $user) {
                $tenantData['users'][] = [
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name'),
                ];
            }

            $results[] = $tenantData;
            Tenant::forgetCurrent();
        }

        return response()->json($results, 200, [], JSON_PRETTY_PRINT);
    })->name('debug.roles');

    Route::get('/debug/fix-roles', function () {
        $tenants = Tenant::with('users.roles')->get();
        $fixed = [];

        foreach ($tenants as $tenant) {
            $tenant->makeCurrent();
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            // Crear roles si no existen
            app(TenantSetupService::class)->provisionTenant($tenant);

            foreach ($tenant->users as $user) {
                $user->refresh();
                if ($user->roles->isEmpty()) {
                    $user->assignRole('Admin');
                    $user->refresh();
                    $fixed[] = "Asignado Admin a {$user->name} en {$tenant->name}";
                }
            }

            Tenant::forgetCurrent();
        }

        return response()->json([
            'mensaje' => 'Fix completado',
            'acciones' => $fixed ?: ['Sin cambios necesarios'],
        ], 200, [], JSON_PRETTY_PRINT);
    })->name('debug.fix-roles');
}

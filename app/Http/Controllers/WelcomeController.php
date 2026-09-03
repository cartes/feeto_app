<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Tenant;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class WelcomeController extends Controller
{
    public function __invoke(): Response
    {
        $posts = BlogPost::query()
            ->where('is_published', true)
            ->orderBy('published_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get()
            ->map(fn (BlogPost $post) => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'summary' => $post->summary,
                'published_at' => $post->published_at?->format('d/m/Y') ?? $post->created_at->format('d/m/Y'),
                'featured_image_url' => $post->featured_image_url,
            ]);

        $tenants = Tenant::query()
            ->where('is_active', true)
            ->where('status', 'active')
            ->withCount(['appointments', 'workOrders'])
            ->get()
            ->sortByDesc(fn (Tenant $t): int => (int) $t->appointments_count + (int) $t->work_orders_count)
            ->take(6)
            ->map(fn (Tenant $t): array => [
                'id' => $t->id,
                'name' => $t->name,
                'slug' => $t->slug,
                'comuna' => $t->comuna,
                'website_url' => $t->website_url,
                'landing_url' => route('taller.landing', ['tenantBySlug' => $t->slug]),
            ])
            ->values()
            ->all();

        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'posts' => $posts,
            'tenants' => $tenants,
            'inventoryImportHighlight' => [
                'eyebrow' => 'Nuevo en inventario',
                'title' => 'Importa tus productos desde Excel y pon tu stock al día en minutos.',
                'description' => 'Sube el archivo que ya usa tu taller y TallerFlow crea o actualiza repuestos, SKU, precios, categorías y stock sin tener que cargar producto por producto.',
                'bullets' => [
                    'Acepta archivos Excel y CSV.',
                    'Actualiza productos existentes por SKU.',
                    'Registra el ajuste de stock al importar.',
                ],
                'tag' => 'Importación masiva',
                'image' => '/images/screenshots/inventario.png',
                'imageWebp' => '/images/screenshots/inventario.webp',
            ],
            'seo' => $this->resolveMarketingSeo('home'),
        ]);
    }
}

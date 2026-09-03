<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Tenant;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class SeoController extends Controller
{
    /**
     * Rutas privadas o transaccionales que no deben rastrearse.
     * (Las páginas de la app del taller viven bajo /taller/{slug}/... y se
     * protegen con <meta name="robots" content="noindex"> desde app.blade.php,
     * ya que comparten prefijo con la landing pública /taller/{slug}).
     */
    private const DISALLOWED_PATHS = [
        '/admin',
        '/login',
        '/register',
        '/forgot-password',
        '/reset-password',
        '/verify-email',
        '/dashboard',
        '/checkout',
        '/ot/',
        '/cotizacion/',
        '/trial/gracias',
    ];

    public function robots(): Response
    {
        $lines = ['User-agent: *', 'Allow: /'];

        foreach (self::DISALLOWED_PATHS as $path) {
            $lines[] = "Disallow: {$path}";
        }

        $lines[] = '';
        $lines[] = 'Sitemap: '.route('sitemap');

        return response(implode("\n", $lines)."\n", 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function sitemap(): Response
    {
        $latestPostDate = BlogPost::where('is_published', true)
            ->max('published_at');

        // 1. Páginas estáticas del sitio público.
        //    Sin <lastmod> artificial: Google solo lo considera cuando es fiable.
        $urls = [
            ['loc' => route('home'), 'changefreq' => 'weekly', 'priority' => '1.0'],
            ['loc' => route('servicios'), 'changefreq' => 'monthly', 'priority' => '0.8'],
            ['loc' => route('pricing'), 'changefreq' => 'monthly', 'priority' => '0.8'],
            ['loc' => route('trial.create'), 'changefreq' => 'monthly', 'priority' => '0.8'],
            ['loc' => route('orden-de-trabajo-taller-mecanico'), 'changefreq' => 'monthly', 'priority' => '0.7'],
            ['loc' => route('talleres.index'), 'changefreq' => 'weekly', 'priority' => '0.8'],
            [
                'loc' => route('blog.index'),
                'lastmod' => $latestPostDate ? Carbon::parse($latestPostDate)->toAtomString() : null,
                'changefreq' => 'weekly',
                'priority' => '0.9',
            ],
        ];

        // 2. Talleres activos (mismo criterio que el directorio público)
        $tenants = Tenant::query()
            ->where('is_active', true)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['slug', 'updated_at']);

        foreach ($tenants as $tenant) {
            $urls[] = [
                'loc' => route('taller.landing', ['tenantBySlug' => $tenant->slug]),
                'lastmod' => $tenant->updated_at?->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ];
        }

        // 3. Artículos del blog
        $posts = BlogPost::query()
            ->where('is_published', true)
            ->orderByDesc('published_at')
            ->get(['slug', 'published_at', 'updated_at']);

        foreach ($posts as $post) {
            $urls[] = [
                'loc' => route('blog.show', ['slug' => $post->slug]),
                'lastmod' => ($post->updated_at ?? $post->published_at)?->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ];
        }

        // 4. Categorías del blog con contenido publicado
        $categories = BlogCategory::query()
            ->whereHas('posts', fn ($query) => $query->where('is_published', true))
            ->orderBy('name')
            ->get(['slug']);

        foreach ($categories as $category) {
            $urls[] = [
                'loc' => route('blog.category', ['slug' => $category->slug]),
                'lastmod' => $latestPostDate ? Carbon::parse($latestPostDate)->toAtomString() : null,
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($urls as $url) {
            $xml .= '<url>';
            $xml .= '<loc>'.htmlspecialchars($url['loc'], ENT_XML1).'</loc>';

            if (filled($url['lastmod'] ?? null)) {
                $xml .= '<lastmod>'.$url['lastmod'].'</lastmod>';
            }

            $xml .= '<changefreq>'.$url['changefreq'].'</changefreq>';
            $xml .= '<priority>'.$url['priority'].'</priority>';
            $xml .= '</url>';
        }

        $xml .= '</urlset>';

        return response($xml, 200)
            ->header('Content-Type', 'application/xml')
            ->header('Cache-Control', 'public, max-age=3600');
    }
}

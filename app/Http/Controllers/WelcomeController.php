<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BlogPost;
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

        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'posts' => $posts,
            'seo' => $this->resolveMarketingSeo(
                'home',
                'TallerFlow · Software para Talleres Mecánicos en Chile',
                'TallerFlow digitaliza la gestión de tu taller mecánico en Chile. Kanban en vivo, recepción con IA, inventario inteligente y WhatsApp automatizado. Prueba gratis 14 días.',
            ),
        ]);
    }
}

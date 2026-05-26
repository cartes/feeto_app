<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PublicBlogController extends Controller
{
    public function index(): Response
    {
        $posts = BlogPost::where('is_published', true)
            ->orderBy('published_at', 'desc')
            ->get();

        $seo = $this->resolveMarketingSeo(
            'blog',
            'Blog · TallerFlow — Gestión de Talleres Mecánicos',
            'Lee artículos prácticos sobre cómo gestionar, digitalizar y expandir tu taller mecánico en Chile.'
        );

        return Inertia::render('Blog/Index', [
            'posts' => $posts,
            'seo' => $seo,
        ]);
    }

    public function show(string $slug): Response
    {
        $post = BlogPost::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        $seo = [
            'title' => "{$post->title} · Blog de TallerFlow",
            'description' => $post->summary ?? Str::limit(strip_tags($post->content), 150),
            'canonical_url' => request()->url(),
            'og_image' => $post->featured_image ?? url('/images/tallerflow-social-share.png'),
            'schema' => [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'BlogPosting',
                    'headline' => $post->title,
                    'description' => $post->summary ?? Str::limit(strip_tags($post->content), 150),
                    'image' => $post->featured_image ?? url('/images/tallerflow-social-share.png'),
                    'datePublished' => $post->published_at?->toAtomString() ?? $post->created_at->toAtomString(),
                    'author' => [
                        '@type' => 'Organization',
                        'name' => 'TallerFlow',
                    ],
                ],
            ],
        ];

        return Inertia::render('Blog/Show', [
            'post' => $post,
            'seo' => $seo,
        ]);
    }
}

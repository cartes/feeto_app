<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Setting;
use App\Support\MarketingSeoPages;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PublicBlogController extends Controller
{
    public function index(): Response
    {
        $posts = BlogPost::with(['categories', 'featuredMedia'])
            ->when(! auth()->check() || ! auth()->user()->is_super_admin, function ($query) {
                $query->where('is_published', true);
            })
            ->orderBy('published_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($post) => array_merge($post->toArray(), [
                'featured_image_url' => $post->featured_image_url,
            ]));

        $categories = BlogCategory::query()
            ->whereHas('posts', function ($query) {
                $query->where('is_published', true);
            })
            ->orderBy('name')
            ->get();

        $canonicalUrl = request()->url();
        $title = Setting::get('seo_blog_title', 'Blog · TallerFlow — Recursos para Talleres Mecánicos');
        $description = Setting::get('seo_blog_description', 'Aprende prácticas para optimizar tiempos, fidelizar clientes y aumentar la rentabilidad de tu taller mecánico en Chile.');
        $image = $this->resolveSocialImage(Setting::get('seo_blog_og_image', ''));
        $ogImage = $image['url'];

        $seo = [
            'title' => $title,
            'description' => $description,
            'canonical_url' => $canonicalUrl,
            'robots' => MarketingSeoPages::DEFAULT_ROBOTS,
            'og_type' => 'website',
            'og_image' => $ogImage,
            'og_image_alt' => 'Blog de TallerFlow',
            'og_image_width' => $image['width'],
            'og_image_height' => $image['height'],
            'twitter_card' => 'summary_large_image',
            'schema' => [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'Blog',
                    '@id' => $canonicalUrl.'#blog',
                    'url' => $canonicalUrl,
                    'name' => 'Blog de TallerFlow',
                    'description' => $description,
                    'inLanguage' => 'es-CL',
                    'publisher' => [
                        '@type' => 'Organization',
                        '@id' => url('/').'#organization',
                        'name' => 'TallerFlow',
                        'logo' => ['@type' => 'ImageObject', 'url' => url(MarketingSeoPages::LOGO_IMAGE)],
                    ],
                ],
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio', 'item' => url('/')],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => $canonicalUrl],
                    ],
                ],
            ],
        ];

        return Inertia::render('Blog/Index', [
            'posts' => $posts,
            'seo' => $seo,
            'categories' => $categories,
            'activeCategory' => null,
        ]);
    }

    public function category(string $slug): Response
    {
        $category = BlogCategory::where('slug', $slug)->firstOrFail();

        $posts = BlogPost::with(['categories', 'featuredMedia'])
            ->whereHas('categories', function ($query) use ($category) {
                $query->where('blog_categories.id', $category->id);
            })
            ->when(! auth()->check() || ! auth()->user()->is_super_admin, function ($query) {
                $query->where('is_published', true);
            })
            ->orderBy('published_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($post) => array_merge($post->toArray(), [
                'featured_image_url' => $post->featured_image_url,
            ]));

        $categories = BlogCategory::query()
            ->whereHas('posts', function ($query) {
                $query->where('is_published', true);
            })
            ->orderBy('name')
            ->get();

        $canonicalUrl = request()->url();
        $title = "Artículos sobre {$category->name} · Blog de TallerFlow";
        $description = "Explora recursos, consejos y guías sobre {$category->name} para optimizar y hacer crecer tu taller mecánico.";
        $image = $this->resolveSocialImage(Setting::get('seo_blog_og_image', ''));
        $ogImage = $image['url'];

        $seo = [
            'title' => $title,
            'description' => $description,
            'canonical_url' => $canonicalUrl,
            'robots' => MarketingSeoPages::DEFAULT_ROBOTS,
            'og_type' => 'website',
            'og_image' => $ogImage,
            'og_image_alt' => "Blog de TallerFlow - {$category->name}",
            'og_image_width' => $image['width'],
            'og_image_height' => $image['height'],
            'twitter_card' => 'summary_large_image',
            'schema' => [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'CollectionPage',
                    '@id' => $canonicalUrl.'#collection',
                    'url' => $canonicalUrl,
                    'name' => $title,
                    'description' => $description,
                    'inLanguage' => 'es-CL',
                    'publisher' => [
                        '@type' => 'Organization',
                        '@id' => url('/').'#organization',
                        'name' => 'TallerFlow',
                        'logo' => ['@type' => 'ImageObject', 'url' => url(MarketingSeoPages::LOGO_IMAGE)],
                    ],
                ],
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio', 'item' => url('/')],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => route('blog.index')],
                        ['@type' => 'ListItem', 'position' => 3, 'name' => $category->name, 'item' => $canonicalUrl],
                    ],
                ],
            ],
        ];

        return Inertia::render('Blog/Index', [
            'posts' => $posts,
            'seo' => $seo,
            'categories' => $categories,
            'activeCategory' => $category,
        ]);
    }

    public function show(string $slug): Response
    {
        $post = BlogPost::with(['categories', 'featuredMedia'])
            ->where('slug', $slug)
            ->when(! auth()->check() || ! auth()->user()->is_super_admin, function ($query) {
                $query->where('is_published', true);
            })
            ->firstOrFail();

        $canonicalUrl = request()->url();
        $defaultImage = $this->resolveSocialImage(Setting::get('seo_blog_og_image', ''));
        $imageUrl = $post->featured_image_url ?? $defaultImage['url'];
        $imageWidth = $post->featured_image_url ? $post->featuredMedia?->width : $defaultImage['width'];
        $imageHeight = $post->featured_image_url ? $post->featuredMedia?->height : $defaultImage['height'];

        $seoTitle = filled($post->meta_title) ? $post->meta_title : "{$post->title} · Blog de TallerFlow";
        $seoDescription = filled($post->meta_description)
            ? $post->meta_description
            : ($post->summary ?? Str::limit(strip_tags($post->content), 155));

        $publishedAt = $post->published_at?->toAtomString() ?? $post->created_at->toAtomString();
        $modifiedAt = $post->updated_at->toAtomString();
        $wordCount = str_word_count(strip_tags($post->content));
        $readingMinutes = max(1, (int) ceil($wordCount / 200));
        $categories = $post->categories->pluck('name')->toArray();

        $seo = [
            'title' => $seoTitle,
            'description' => $seoDescription,
            'canonical_url' => $canonicalUrl,
            'robots' => $post->is_published ? MarketingSeoPages::DEFAULT_ROBOTS : MarketingSeoPages::NOINDEX_ROBOTS,
            'og_type' => 'article',
            'og_image' => $imageUrl,
            'og_image_alt' => $post->featuredMedia?->alt_text ?? $post->title,
            'og_image_width' => $imageWidth,
            'og_image_height' => $imageHeight,
            'published_time' => $publishedAt,
            'modified_time' => $modifiedAt,
            'author' => 'TallerFlow',
            'categories' => $categories,
            'reading_minutes' => $readingMinutes,
            'word_count' => $wordCount,
            'twitter_card' => 'summary_large_image',
            'schema' => [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'BlogPosting',
                    '@id' => $canonicalUrl.'#article',
                    'headline' => $post->title,
                    'description' => $seoDescription,
                    'image' => [
                        '@type' => 'ImageObject',
                        'url' => $imageUrl,
                        'width' => $imageWidth,
                        'height' => $imageHeight,
                    ],
                    'datePublished' => $publishedAt,
                    'dateModified' => $modifiedAt,
                    'author' => [
                        '@type' => 'Organization',
                        '@id' => url('/').'#organization',
                        'name' => 'TallerFlow',
                        'url' => url('/'),
                    ],
                    'publisher' => [
                        '@type' => 'Organization',
                        '@id' => url('/').'#organization',
                        'name' => 'TallerFlow',
                        'logo' => ['@type' => 'ImageObject', 'url' => url(MarketingSeoPages::LOGO_IMAGE)],
                    ],
                    'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $canonicalUrl],
                    'url' => $canonicalUrl,
                    'inLanguage' => 'es-CL',
                    'articleSection' => $categories,
                    'wordCount' => $wordCount,
                ],
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio', 'item' => url('/')],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => route('blog.index')],
                        ['@type' => 'ListItem', 'position' => 3, 'name' => $post->title, 'item' => $canonicalUrl],
                    ],
                ],
            ],
        ];

        return Inertia::render('Blog/Show', [
            'post' => array_merge($post->toArray(), ['featured_image_url' => $imageUrl]),
            'seo' => $seo,
        ]);
    }
}

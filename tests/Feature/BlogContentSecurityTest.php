<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\User;
use App\Services\BlogContentSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BlogContentSecurityTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('unsafeHtml')]
    public function test_unsafe_html_is_removed(string $html, string $expected = '<p>Seguro</p>'): void
    {
        $sanitized = app(BlogContentSanitizer::class)->sanitize($html);

        $this->assertSame($expected, $sanitized);
    }

    /** @return array<string, array{0: string, 1?: string}> */
    public static function unsafeHtml(): array
    {
        return [
            'script' => ['<p>Seguro</p><script>alert(1)</script>'],
            'event handler' => ['<p onclick="alert(1)">Seguro</p>'],
            'javascript url' => ['<p><a href="javascript:alert(1)">Seguro</a></p>', '<p><a>Seguro</a></p>'],
            'encoded javascript url' => ['<p><a href="jav&#x61;script:alert(1)">Seguro</a></p>', '<p><a>Seguro</a></p>'],
            'svg' => ['<p>Seguro</p><svg onload="alert(1)"></svg>'],
            'iframe' => ['<p>Seguro</p><iframe src="https://example.com"></iframe>'],
            'dangerous css' => ['<p style="background-image:url(javascript:alert(1))">Seguro</p>'],
            'image event handler' => ['<p>Seguro</p><img src="/foto.webp" onerror="alert(1)" alt="Foto" />', '<p>Seguro</p><img src="/foto.webp" alt="Foto" />'],
            'data url' => ['<p><a href="data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==">Seguro</a></p>', '<p><a>Seguro</a></p>'],
            'form' => ['<form action="https://example.com"><p>Seguro</p><input name="password" /></form>'],
        ];
    }

    public function test_editor_formatting_is_preserved(): void
    {
        $html = '<h2 style="text-align:center">Título</h2><p><strong>Negrita</strong> <em>Cursiva</em> <u>Subrayado</u></p><ul><li>Elemento</li></ul><blockquote><p>Cita</p></blockquote><pre><code>&lt;script&gt;</code></pre><p><a href="https://example.com" target="_blank">Enlace</a><img src="/storage/blog/test.webp" alt="Foto" class="tiptap-image" /></p><table class="tiptap-table"><tbody><tr><th colspan="2">Cabecera</th></tr><tr><td>Uno</td><td>Dos</td></tr></tbody></table>';
        $sanitized = app(BlogContentSanitizer::class)->sanitize($html);

        foreach (['text-align:center', '<strong>Negrita</strong>', '<em>Cursiva</em>', '<u>Subrayado</u>', '<ul>', '<blockquote>', '<pre><code>&lt;script&gt;</code></pre>', 'href="https://example.com"', 'src="/storage/blog/test.webp"', 'class="tiptap-image"', '<table', 'colspan="2"'] as $fragment) {
            $this->assertStringContainsString($fragment, $sanitized);
        }
        $this->assertSame($sanitized, app(BlogContentSanitizer::class)->sanitize($sanitized));
    }

    public function test_create_and_update_store_sanitized_content(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create());
        $data = ['title' => 'Artículo seguro', 'content' => '<p onclick="alert(1)">Seguro</p><script>alert(1)</script>', 'is_published' => true];

        $this->post(route('admin.blog.store'), $data)->assertSessionHasNoErrors()->assertRedirect(route('admin.blog.index'));
        $post = BlogPost::query()->firstOrFail();
        $this->assertDatabaseHas('blog_posts', ['id' => $post->id, 'content' => '<p>Seguro</p>']);

        $data['content'] = '<p onmouseover="alert(1)">Editado</p>';
        $this->put(route('admin.blog.update', $post), $data)->assertSessionHasNoErrors()->assertRedirect(route('admin.blog.index'));
        $this->assertDatabaseHas('blog_posts', ['id' => $post->id, 'content' => '<p>Editado</p>']);
    }

    public function test_legacy_content_is_sanitized_for_visitors_and_admin_editor(): void
    {
        $post = BlogPost::create(['title' => 'Antiguo', 'slug' => 'antiguo', 'content' => '<p>Seguro</p>', 'is_published' => true, 'published_at' => now()]);
        $unsafeContent = '<p onclick="alert(1)">Seguro</p><script>alert(1)</script>';
        BlogPost::query()->whereKey($post->id)->update(['content' => $unsafeContent]);

        $this->assertSame('<p>Seguro</p>', $post->fresh()->toArray()['content']);
        $this->get(route('blog.show', $post->slug))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Blog/Show')->where('post.content', '<p>Seguro</p>'));
        $this->actingAs(User::factory()->superAdmin()->create())->get(route('admin.blog.edit', $post))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Blog/Form')->where('post.content', '<p>Seguro</p>'));
        $this->assertDatabaseHas('blog_posts', ['id' => $post->id, 'content' => $unsafeContent]);
    }
}

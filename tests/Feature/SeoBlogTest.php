<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoBlogTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->superAdmin = User::factory()->superAdmin()->create();
    }

    public function test_robots_txt_returns_text_plain_content(): void
    {
        $response = $this->get(route('robots'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $response->assertSee('User-agent: *');
        $response->assertSee('Sitemap: '.route('sitemap'));
    }

    public function test_sitemap_xml_returns_xml_content_with_dynamic_urls(): void
    {
        // Crear un tenant activo
        $tenant = Tenant::factory()->create([
            'slug' => 'taller-activo',
            'is_active' => true,
        ]);

        // Crear un tenant inactivo (no debe aparecer en sitemap)
        Tenant::factory()->create([
            'slug' => 'taller-inactivo',
            'is_active' => false,
        ]);

        // Crear posts (uno publicado, uno borrador)
        $publishedPost = BlogPost::create([
            'title' => 'Post Publicado',
            'slug' => 'post-publicado',
            'content' => 'Contenido',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $draftPost = BlogPost::create([
            'title' => 'Post Borrador',
            'slug' => 'post-borrador',
            'content' => 'Contenido',
            'is_published' => false,
        ]);

        // Crear categorías
        $activeCategory = BlogCategory::create([
            'name' => 'Categoría Activa',
            'slug' => 'categoria-activa',
            'color' => '#FF7A00',
        ]);

        $inactiveCategory = BlogCategory::create([
            'name' => 'Categoría Inactiva',
            'slug' => 'categoria-inactiva',
            'color' => '#FF7A00',
        ]);

        // Vincular categoría activa al post publicado
        $publishedPost->categories()->attach($activeCategory->id);

        $response = $this->get(route('sitemap'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml');

        // Debe contener páginas estáticas
        $response->assertSee(route('home'), false);
        $response->assertSee(route('pricing'), false);
        $response->assertSee(route('trial.create'), false);
        $response->assertSee(route('blog.index'), false);

        // Debe contener el taller activo
        $response->assertSee(route('taller.landing', 'taller-activo'), false);

        // No debe contener el taller inactivo
        $response->assertDontSee(route('taller.landing', 'taller-inactivo'), false);

        // Debe contener el post publicado
        $response->assertSee(route('blog.show', 'post-publicado'), false);

        // No debe contener el post borrador
        $response->assertDontSee(route('blog.show', 'post-borrador'), false);

        // Debe contener la categoría activa
        $response->assertSee(route('blog.category', 'categoria-activa'), false);

        // No debe contener la categoría inactiva
        $response->assertDontSee(route('blog.category', 'categoria-inactiva'), false);
    }

    public function test_super_admin_can_access_blog_crud(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.blog.index'));
        $response->assertOk();

        $response = $this->actingAs($this->superAdmin)->get(route('admin.blog.create'));
        $response->assertOk();
    }

    public function test_regular_user_cannot_access_blog_crud(): void
    {
        $user = User::factory()->create(['is_super_admin' => false]);

        $response = $this->actingAs($user)->get(route('admin.blog.index'));
        $response->assertForbidden();

        $response = $this->actingAs($user)->post(route('admin.blog.store'), [
            'title' => 'Post Invasivo',
            'content' => 'Contenido',
            'is_published' => false,
        ]);
        $response->assertForbidden();
    }

    public function test_super_admin_can_create_and_update_blog_posts(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('admin.blog.store'), [
            'title' => 'Mi Primer Articulo',
            'summary' => 'Resumen del post',
            'content' => '<p>Contenido del post</p>',
            'is_published' => true,
        ]);

        $response->assertRedirect(route('admin.blog.index'));
        $this->assertDatabaseHas('blog_posts', [
            'title' => 'Mi Primer Articulo',
            'slug' => 'mi-primer-articulo',
            'is_published' => true,
        ]);

        $post = BlogPost::first();

        $response = $this->actingAs($this->superAdmin)->put(route('admin.blog.update', $post->id), [
            'title' => 'Mi Primer Articulo Modificado',
            'summary' => 'Resumen editado',
            'content' => '<p>Contenido editado</p>',
            'is_published' => false,
        ]);

        $response->assertRedirect(route('admin.blog.index'));
        $this->assertDatabaseHas('blog_posts', [
            'title' => 'Mi Primer Articulo Modificado',
            'slug' => 'mi-primer-articulo-modificado',
            'is_published' => false,
        ]);
    }

    public function test_public_blog_pages(): void
    {
        $post = BlogPost::create([
            'title' => 'Post Publico Test',
            'slug' => 'post-publico-test',
            'summary' => 'Resumen publico',
            'content' => '<p>Contenido del articulo publico</p>',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->withoutVite();

        $response = $this->get(route('blog.index'));
        $response->assertOk();
        $response->assertSee('Post Publico Test');

        $response = $this->get(route('blog.show', $post->slug));
        $response->assertOk();
        $response->assertSee('Post Publico Test');
        $response->assertSee('Contenido del articulo publico');
    }

    public function test_public_blog_category_pages(): void
    {
        $category = BlogCategory::create([
            'name' => 'Consejos de Gestión',
            'slug' => 'consejos-de-gestion',
            'color' => '#10B981',
        ]);

        $post = BlogPost::create([
            'title' => 'Como gestionar un taller',
            'slug' => 'como-gestionar-un-taller',
            'summary' => 'Resumen de gestion',
            'content' => '<p>Contenido de gestion</p>',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $post->categories()->attach($category->id);

        $this->withoutVite();

        $response = $this->get(route('blog.category', $category->slug));
        $response->assertOk();
        $response->assertSee('Consejos de Gestión');
        $response->assertSee('Como gestionar un taller');
    }

    public function test_super_admin_can_see_drafts_on_public_blog_pages(): void
    {
        $draftPost = BlogPost::create([
            'title' => 'Post Borrador Interno',
            'slug' => 'post-borrador-interno',
            'summary' => 'Resumen borrador',
            'content' => '<p>Contenido borrador</p>',
            'is_published' => false,
        ]);

        $this->withoutVite();

        // Guest user should not see draft post
        $response = $this->get(route('blog.index'));
        $response->assertOk();
        $response->assertDontSee('Post Borrador Interno');

        $response = $this->get(route('blog.show', $draftPost->slug));
        $response->assertNotFound();

        // Super-admin user should see draft post
        $response = $this->actingAs($this->superAdmin)->get(route('blog.index'));
        $response->assertOk();
        $response->assertSee('Post Borrador Interno');

        $response = $this->actingAs($this->superAdmin)->get(route('blog.show', $draftPost->slug));
        $response->assertOk();
        $response->assertSee('Post Borrador Interno');
        $response->assertSee('Contenido borrador');
    }
}

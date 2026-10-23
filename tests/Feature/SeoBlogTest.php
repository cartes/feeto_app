<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Tenant;
use App\Models\User;
use App\Support\MarketingSeoPages;
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

    public function test_robots_txt_disallows_private_paths(): void
    {
        $response = $this->get(route('robots'));

        $response->assertOk();
        $response->assertSee('Disallow: /admin', false);
        $response->assertSee('Disallow: /ot/', false);
        $response->assertSee('Disallow: /cotizacion/', false);
        $response->assertSee('Sitemap: '.route('sitemap'), false);
        $this->assertFileDoesNotExist(public_path('robots.txt'));
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
        $response->assertSee(route('servicios'), false);
        $response->assertSee(route('orden-de-trabajo-taller-mecanico'), false);
        $response->assertSee(route('trial.create'), false);
        $response->assertSee(route('blog.index'), false);
        $response->assertSee(route('talleres.index'), false);

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

        $this->assertDatabaseHas('blog_posts', [
            'title' => 'Mi Primer Articulo Modificado',
            'slug' => 'mi-primer-articulo-modificado',
            'is_published' => false,
        ]);
    }

    public function test_super_admin_can_create_and_update_blog_posts_with_custom_slug(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('admin.blog.store'), [
            'title' => 'Mi Articulo con Slug',
            'slug' => 'custom-slug-articulo',
            'summary' => 'Resumen del post',
            'content' => '<p>Contenido del post</p>',
            'is_published' => true,
        ]);

        $response->assertRedirect(route('admin.blog.index'));
        $this->assertDatabaseHas('blog_posts', [
            'title' => 'Mi Articulo con Slug',
            'slug' => 'custom-slug-articulo',
            'is_published' => true,
        ]);

        $post = BlogPost::where('title', 'Mi Articulo con Slug')->firstOrFail();

        $response = $this->actingAs($this->superAdmin)->put(route('admin.blog.update', $post->id), [
            'title' => 'Mi Articulo con Slug',
            'slug' => 'otro-slug-diferente',
            'summary' => 'Resumen editado',
            'content' => '<p>Contenido editado</p>',
            'is_published' => true,
        ]);

        $response->assertRedirect(route('admin.blog.index'));
        $this->assertDatabaseHas('blog_posts', [
            'id' => $post->id,
            'slug' => 'otro-slug-diferente',
        ]);

        $response = $this->actingAs($this->superAdmin)->put(route('admin.blog.update', $post->id), [
            'title' => 'Titulo Nuevo Para Fallback',
            'slug' => '',
            'summary' => 'Resumen editado',
            'content' => '<p>Contenido editado</p>',
            'is_published' => true,
        ]);

        $response->assertRedirect(route('admin.blog.index'));
        $this->assertDatabaseHas('blog_posts', [
            'id' => $post->id,
            'slug' => 'titulo-nuevo-para-fallback',
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

    public function test_public_blog_pages_include_blog_category_route_in_ziggy(): void
    {
        $this->withoutVite();

        $response = $this->get(route('blog.index'));

        $response->assertOk();
        $response->assertSee('"blog.category"', false);
        $response->assertSee('"uri":"blog\\/categoria\\/{slug}"', false);
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

    public function test_tutorials_index_uses_content_type_without_requiring_a_category(): void
    {
        $this->withoutVite();
        BlogPost::create(['title' => 'Tutorial', 'slug' => 'tutorial', 'content' => '<p>Guía</p>', 'content_type' => 'tutorial', 'is_published' => true]);
        BlogPost::create(['title' => 'Artículo', 'slug' => 'articulo', 'content' => '<p>Artículo</p>', 'is_published' => true]);
        BlogPost::create(['title' => 'Borrador', 'slug' => 'borrador', 'content' => '<p>Borrador</p>', 'content_type' => 'tutorial', 'is_published' => false]);

        $this->get(route('tutorials.index'))->assertOk()->assertInertia(fn ($page) => $page
            ->component('Blog/Index')->where('tutorialsOnly', true)->has('posts', 1)
            ->where('posts.0.slug', 'tutorial'));
        $this->get(route('home'))->assertInertia(fn ($page) => $page
            ->has('tutorialPosts', 1)->where('tutorialPosts.0.slug', 'tutorial'));
    }

    public function test_tutorials_index_renders_an_empty_list_when_no_tutorials_exist(): void
    {
        $this->withoutVite();
        $this->get(route('tutorials.index'))->assertOk()->assertInertia(fn ($page) => $page
            ->where('tutorialsOnly', true)->has('posts', 0));
    }

    public function test_tutorial_urls_remain_stable_when_categories_change_and_redirect_old_paths(): void
    {
        $this->withoutVite();
        $tutorial = BlogCategory::create(['name' => 'Tutoriales', 'slug' => 'tutoriales']);
        $other = BlogCategory::create(['name' => 'Gestión', 'slug' => 'gestion']);
        $post = BlogPost::create([
            'content_type' => 'tutorial',
            'title' => 'Gestionar una OT',
            'slug' => 'gestionar-ot',
            'content' => '<p>Guía de trabajo</p>',
            'is_published' => true,
            'published_at' => now(),
        ]);
        $post->categories()->attach($tutorial);
        $tutorialUrl = route('tutorial.show', $post->slug);
        $blogUrl = route('blog.show', $post->slug);

        $this->get($blogUrl)->assertStatus(301)->assertRedirect($tutorialUrl);
        $this->get($tutorialUrl)->assertOk()->assertInertia(fn ($page) => $page
            ->component('Blog/Show')
            ->where('post.public_url', $tutorialUrl)
            ->where('seo.canonical_url', $tutorialUrl)
            ->where('seo.schema.0.url', $tutorialUrl));
        $this->get(route('blog.index'))->assertInertia(fn ($page) => $page
            ->where('posts.0.public_url', $tutorialUrl));
        $this->get(route('blog.category', $tutorial->slug))->assertInertia(fn ($page) => $page
            ->where('posts.0.public_url', $tutorialUrl));
        $this->get(route('home'))->assertInertia(fn ($page) => $page
            ->where('posts.0.public_url', $tutorialUrl)
            ->where('tutorialPosts.0.public_url', $tutorialUrl));
        $this->get(route('sitemap'))->assertSee($tutorialUrl, false)->assertDontSee($blogUrl, false);
        $this->actingAs($this->superAdmin)->get(route('admin.blog.index'))->assertInertia(fn ($page) => $page
            ->where('posts.0.public_url', $tutorialUrl));
        auth()->logout();

        $post->categories()->attach($other);
        $this->get($tutorialUrl)->assertOk();
        $this->get($blogUrl)->assertStatus(301)->assertRedirect($tutorialUrl);
        $post->categories()->sync([$other->id]);
        $this->get($tutorialUrl)->assertOk();
        $post->categories()->detach();
        $this->get($tutorialUrl)->assertOk();
        $post->update(['content_type' => 'article']);
        $this->get($tutorialUrl)->assertStatus(301)->assertRedirect($blogUrl);
        $this->get($blogUrl)->assertOk()->assertInertia(fn ($page) => $page
            ->where('seo.canonical_url', $blogUrl));
        $this->get(route('sitemap'))->assertSee($blogUrl, false)->assertDontSee($tutorialUrl, false);
    }

    public function test_tutorial_routes_do_not_expose_drafts_or_missing_posts(): void
    {
        $this->withoutVite();
        $category = BlogCategory::create(['name' => 'Tutoriales', 'slug' => 'tutoriales']);
        $post = BlogPost::create([
            'content_type' => 'tutorial',
            'title' => 'Tutorial borrador',
            'slug' => 'tutorial-borrador',
            'content' => '<p>Contenido privado</p>',
            'is_published' => false,
        ]);
        $post->categories()->attach($category);

        $this->get(route('tutorial.show', $post->slug))->assertNotFound();
        $this->get(route('blog.show', $post->slug))->assertNotFound();
        $this->get(route('tutorial.show', 'no-existe'))->assertNotFound();
        $this->get(route('blog.show', 'no-existe'))->assertNotFound();
        $this->actingAs($this->superAdmin)->get(route('tutorial.show', $post->slug))
            ->assertOk()->assertInertia(fn ($page) => $page
            ->where('seo.robots', MarketingSeoPages::NOINDEX_ROBOTS));
        $this->get(route('blog.show', $post->slug))
            ->assertStatus(301)->assertRedirect(route('tutorial.show', $post->slug));
    }

    public function test_admin_can_choose_content_type_and_categories_do_not_override_it(): void
    {
        $category = BlogCategory::create(['name' => 'Gestión', 'slug' => 'gestion']);
        $data = ['title' => 'Guía de órdenes', 'slug' => 'guia-ordenes', 'content' => '<p>Guía</p>', 'is_published' => true, 'content_type' => 'tutorial', 'category_ids' => [$category->id]];
        $this->actingAs($this->superAdmin)->post(route('admin.blog.store'), $data)->assertSessionHasNoErrors();
        $post = BlogPost::query()->where('slug', 'guia-ordenes')->firstOrFail();
        $this->assertTrue($post->isTutorial());
        $this->put(route('admin.blog.update', $post), [...$data, 'category_ids' => []])->assertSessionHasNoErrors();
        $this->assertTrue($post->fresh()->isTutorial());
        $this->put(route('admin.blog.update', $post), [...$data, 'content_type' => 'article'])->assertSessionHasNoErrors();
        $this->assertFalse($post->fresh()->isTutorial());
        $this->post(route('admin.blog.store'), [...$data, 'content_type' => 'invalid'])->assertSessionHasErrors('content_type');
        $this->put(route('admin.blog.update', $post), [...$data, 'content_type' => 'invalid'])->assertSessionHasErrors('content_type');
    }

    public function test_migration_preserves_existing_exclusive_tutorials(): void
    {
        $category = BlogCategory::create(['name' => 'Tutoriales', 'slug' => 'tutoriales']);
        $other = BlogCategory::create(['name' => 'Gestión', 'slug' => 'gestion']);
        $exclusive = BlogPost::create(['title' => 'Tutorial anterior', 'slug' => 'anterior', 'content' => 'Guía']);
        $mixed = BlogPost::create(['title' => 'Mixto', 'slug' => 'mixto', 'content' => 'Artículo']);
        $plain = BlogPost::create(['title' => 'Sin categoría', 'slug' => 'sin-categoria', 'content' => 'Artículo']);
        $exclusive->categories()->attach($category);
        $mixed->categories()->attach([$category->id, $other->id]);
        $migration = require database_path('migrations/2026_10_23_143808_add_content_type_to_blog_posts_table.php');
        $migration->down();
        $migration->up();
        $this->assertTrue($exclusive->fresh()->isTutorial());
        $this->assertFalse($mixed->fresh()->isTutorial());
        $this->assertFalse($plain->fresh()->isTutorial());
    }
}

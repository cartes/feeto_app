<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBlogPostRequest;
use App\Http\Requests\Admin\UpdateBlogPostRequest;
use App\Models\AuditLog;
use App\Models\BlogPost;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BlogPostController extends Controller
{
    public function index(): Response
    {
        $posts = BlogPost::orderBy('created_at', 'desc')->get();

        return Inertia::render('Admin/Blog/Index', [
            'posts' => $posts,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Blog/Form', [
            'post' => null,
        ]);
    }

    public function store(StoreBlogPostRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = BlogPost::generateUniqueSlug($data['title']);

        if ($data['is_published']) {
            $data['published_at'] = now();
        }

        $post = BlogPost::create($data);

        AuditLog::record('blog_post.created', "Super-admin creó artículo de blog: {$post->title}", null, [
            'id' => $post->id,
            'slug' => $post->slug,
        ]);

        return redirect()->route('admin.blog.index')->with('success', 'Artículo creado correctamente.');
    }

    public function edit(BlogPost $blog): Response
    {
        return Inertia::render('Admin/Blog/Form', [
            'post' => $blog,
        ]);
    }

    public function update(UpdateBlogPostRequest $request, BlogPost $blog): RedirectResponse
    {
        $data = $request->validated();

        if ($data['title'] !== $blog->title) {
            $data['slug'] = BlogPost::generateUniqueSlug($data['title']);
        }

        if ($data['is_published'] && ! $blog->is_published) {
            $data['published_at'] = now();
        } elseif (! $data['is_published']) {
            $data['published_at'] = null;
        }

        $blog->update($data);

        AuditLog::record('blog_post.updated', "Super-admin actualizó artículo de blog: {$blog->title}", null, [
            'id' => $blog->id,
            'slug' => $blog->slug,
        ]);

        return redirect()->route('admin.blog.index')->with('success', 'Artículo actualizado correctamente.');
    }

    public function destroy(BlogPost $blog): RedirectResponse
    {
        $title = $blog->title;
        $blog->delete();

        AuditLog::record('blog_post.deleted', "Super-admin eliminó artículo de blog: {$title}");

        return redirect()->route('admin.blog.index')->with('success', 'Artículo eliminado correctamente.');
    }
}

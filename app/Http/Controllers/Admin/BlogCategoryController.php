<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBlogCategoryRequest;
use App\Http\Requests\Admin\UpdateBlogCategoryRequest;
use App\Models\AuditLog;
use App\Models\BlogCategory;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BlogCategoryController extends Controller
{
    public function index(): Response
    {
        $categories = BlogCategory::withCount('posts')->orderBy('name')->get();

        return Inertia::render('Admin/Blog/Categories/Index', [
            'categories' => $categories,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Blog/Categories/Form', [
            'category' => null,
        ]);
    }

    public function store(StoreBlogCategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = BlogCategory::generateUniqueSlug($data['name']);

        $category = BlogCategory::create($data);

        AuditLog::record('blog_category.created', "Super-admin creó categoría: {$category->name}");

        return redirect()->route('admin.blog-categories.index')->with('success', 'Categoría creada correctamente.');
    }

    public function edit(BlogCategory $blogCategory): Response
    {
        return Inertia::render('Admin/Blog/Categories/Form', [
            'category' => $blogCategory,
        ]);
    }

    public function update(UpdateBlogCategoryRequest $request, BlogCategory $blogCategory): RedirectResponse
    {
        $data = $request->validated();

        if ($data['name'] !== $blogCategory->name) {
            $data['slug'] = BlogCategory::generateUniqueSlug($data['name']);
        }

        $blogCategory->update($data);

        AuditLog::record('blog_category.updated', "Super-admin actualizó categoría: {$blogCategory->name}");

        return redirect()->route('admin.blog-categories.index')->with('success', 'Categoría actualizada correctamente.');
    }

    public function destroy(BlogCategory $blogCategory): RedirectResponse
    {
        $name = $blogCategory->name;
        $blogCategory->delete();

        AuditLog::record('blog_category.deleted', "Super-admin eliminó categoría: {$name}");

        return redirect()->route('admin.blog-categories.index')->with('success', 'Categoría eliminada correctamente.');
    }
}

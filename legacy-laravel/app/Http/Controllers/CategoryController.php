<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use App\Services\ActivityLogger;
use App\Services\CategoryService;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryService $categoryService
    ) {}

    public function index()
    {
        $categories = $this->categoryService->getAll();

        return view('categories.index', compact('categories'));
    }

    public function create()
    {
        return view('categories.create');
    }

    public function store(CategoryRequest $request)
    {
        $category = $this->categoryService->store($request->validated());
        app(ActivityLogger::class)->log('category.create', 'Kategori "'.$category->name.'" ditambahkan.');

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function edit(Category $category)
    {
        return view('categories.edit', compact('category'));
    }

    public function update(CategoryRequest $request, Category $category)
    {
        $this->categoryService->update($category, $request->validated());
        app(ActivityLogger::class)->log('category.update', 'Kategori "'.$category->name.'" diperbarui.');

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $category)
    {
        try {
            $this->categoryService->delete($category);
        } catch (\RuntimeException $e) {
            return redirect()->route('categories.index')->with('error', $e->getMessage());
        }
        app(ActivityLogger::class)->log('category.delete', 'Kategori "'.$category->name.'" dihapus.');

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil dihapus.');
    }

    public function toggleActive(Category $category)
    {
        $newStatus = ! $category->is_active;
        $category->update(['is_active' => $newStatus]);

        $status = $newStatus ? 'diaktifkan' : 'dinonaktifkan';
        app(ActivityLogger::class)->log('category.toggle_active', 'Kategori "'.$category->name.'" '.$status.'.');

        return back()->with('success', 'Kategori "'.$category->name.'" berhasil '.$status.'.');
    }
}

<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Str;

class CategoryService
{
    public function getAll()
    {
        return Category::withCount('products')->latest()->paginate(15);
    }

    public function store(array $data): Category
    {
        $data['slug'] = Str::slug($data['name']);

        return Category::create($data);
    }

    public function update(Category $category, array $data): Category
    {
        $data['slug'] = Str::slug($data['name']);
        $category->update($data);

        return $category;
    }

    public function delete(Category $category): bool
    {
        if ($category->products()->exists()) {
            throw new \RuntimeException('Kategori tidak dapat dihapus karena masih memiliki produk.');
        }

        return $category->delete();
    }

    public function getActive()
    {
        return Category::where('is_active', true)->orderBy('name')->get();
    }
}

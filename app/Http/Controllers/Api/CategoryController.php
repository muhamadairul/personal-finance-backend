<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::forUser($request->user()->id)
            ->orderBy('type')
            ->orderBy('id')
            ->get();

        return CategoryResource::collection($categories);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'icon'  => 'required|integer',
            'color' => 'required|integer',
            'type'  => 'required|in:income,expense',
        ]);

        $validated['user_id'] = $request->user()->id;
        // dd($validated);

        $category = Category::create($validated);

        return new CategoryResource($category);
    }

    public function show(Request $request, Category $category)
    {
        // Allow viewing global categories or own categories
        if (!is_null($category->user_id) && $category->user_id !== $request->user()->id) {
            abort(403);
        }

        return new CategoryResource($category);
    }

    public function update(Request $request, Category $category)
    {
        // Can only update own categories, not global ones
        if (is_null($category->user_id) || $category->user_id !== $request->user()->id) {
            abort(403, 'Tidak dapat mengubah kategori default.');
        }

        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'icon'  => 'required|integer',
            'color' => 'required|integer',
            'type'  => 'required|in:income,expense',
        ]);

        $category->update($validated);

        return new CategoryResource($category);
    }

    public function destroy(Request $request, Category $category)
    {
        if (is_null($category->user_id) || $category->user_id !== $request->user()->id) {
            abort(403, 'Tidak dapat menghapus kategori default.');
        }

        $category->delete();

        return response()->json(['message' => 'Kategori berhasil dihapus']);
    }
}

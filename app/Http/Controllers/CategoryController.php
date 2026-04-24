<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function getCategory()
    {
        $category = Category::with([
            'SubCategory' => function ($q) {
                $q->withCount('products');
            }
        ]) 
            ->withCount('products')
            ->get();
        return apiResponse($category, 200, 'get sucessfully');
    }
    public function AddCategory(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
               'type' => 'required|in:medicine,cosmetic,equipment'

        ]);
        $category = Category::create($data);
        return apiResponse($category, 200, 'add sucessfully');
    }
    public function UpdateCategory(Request $request, $id)
    {
        $category = Category::findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $category->update($data);

        return apiResponse($category, 200, 'Updated');
    }
    public function DeleteCategory($id)
    {
        Category::findOrFail($id)->delete();
        return apiResponse(null, 200, 'delete success');
    }
}

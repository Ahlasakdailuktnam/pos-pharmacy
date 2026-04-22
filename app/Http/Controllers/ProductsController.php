<?php

namespace App\Http\Controllers;

use App\Models\Products;
use Illuminate\Http\Request;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class ProductsController extends Controller
{
    // Get all products
    public function GetProduct()
    {
        $product = Products::with(['category', 'SubCategory'])
            ->latest()
            ->get();

        return apiResponse($product, 200, 'Get product successfully');
    }

    // Add product with image upload
    public function AddProduct(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',

            'category_id' => 'required|exists:categories,id',
            'sub_category_id' => 'nullable|exists:sub_categories,id',

            'unit' => 'required|string',

            'price_per_unit' => 'nullable|numeric',
            'price_per_box' => 'nullable|numeric',
            'box_size' => 'nullable|integer',

            'cost' => 'nullable|numeric',

            'stock_box' => 'required|integer|min:0',

            'expiry_date' => 'nullable|date',
            'manufacturer' => 'nullable|string|max:255',
            'prescription_required' => 'boolean',

            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',

            // image
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // Upload image to Cloudinary
        if ($request->hasFile('image')) {
            $upload = Cloudinary::upload(
                $request->file('image')->getRealPath(),
                [
                    'folder' => 'products'
                ]
            );

            $data['image'] = $upload->getSecurePath();
            $data['image_public_id'] = $upload->getPublicId();
        }

        // Auto stock unit
        $data['stock_unit'] =
            ($data['stock_box'] ?? 0) *
            ($data['box_size'] ?? 1);

        $product = Products::create($data);

        return apiResponse($product, 200, 'Add product successfully');
    }

    // Get one product
    public function GetProductById($id)
    {
        $product = Products::with(['category', 'SubCategory'])
            ->findOrFail($id);

        return apiResponse($product, 200, 'Get product successfully');
    }

    // Update product
    public function UpdateProduct(Request $request, $id)
    {
        $product = Products::findOrFail($id);

        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'name_en' => 'nullable|string|max:255',

            'category_id' => 'required|exists:categories,id',
            'sub_category_id' => 'nullable|exists:sub_categories,id',

            'unit' => 'required|string',

            'price_per_unit' => 'nullable|numeric',
            'price_per_box' => 'nullable|numeric',
            'box_size' => 'nullable|integer',

            'cost' => 'nullable|numeric',

            'stock_box' => 'required|integer|min:0',

            'expiry_date' => 'nullable|date',
            'manufacturer' => 'nullable|string|max:255',
            'prescription_required' => 'boolean',

            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',

            // image
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // Replace image
        if ($request->hasFile('image')) {

            // delete old image
            if ($product->image_public_id) {
                Cloudinary::destroy($product->image_public_id);
            }

            $upload = Cloudinary::upload(
                $request->file('image')->getRealPath(),
                [
                    'folder' => 'products'
                ]
            );

            $data['image'] = $upload->getSecurePath();
            $data['image_public_id'] = $upload->getPublicId();
        }

        // Auto stock unit
        $data['stock_unit'] =
            ($data['stock_box'] ?? 0) *
            ($data['box_size'] ?? 1);

        $product->update($data);

        return apiResponse($product, 200, 'Update product successfully');
    }

    // Delete product
    public function DeleteProducts($id)
    {
        $product = Products::findOrFail($id);

        // delete image from cloudinary
        if ($product->image_public_id) {
            Cloudinary::destroy($product->image_public_id);
        }

        $product->delete();

        return apiResponse([], 200, 'Delete successfully');
    }

    // Search
    public function SearchProduct($keyword)
    {
        $product = Products::where('name_en', 'like', '%' . $keyword . '%')
            ->get();

        return apiResponse($product, 200, 'Search found');
    }

    // Low stock
    public function LowStock()
    {
        $product = Products::where('stock_unit', '<=', 10)->get();

        return apiResponse($product, 200, 'Low stock found');
    }

    // Expired
    public function expried()
    {
        $product = Products::whereDate('expiry_date', '<=', now())->get();

        return apiResponse($product, 200, 'Expired product found');
    }
}

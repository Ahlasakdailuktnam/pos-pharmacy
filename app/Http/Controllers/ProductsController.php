<?php

namespace App\Http\Controllers;

use App\Models\Products;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class ProductsController extends Controller
{

    public function GetProduct()
    {
        $products = Products::with([
            'category',
            'SubCategory',
            'unit'
        ])->latest()->get();

        return apiResponse($products, 200, 'Get product successfully');
    }


    public function AddProduct(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',

            'category_id' => 'required|exists:categories,id',
            'sub_category_id' => 'nullable|exists:sub_categories,id',

            'unit_id' => 'nullable|exists:units,id',

            'price_per_unit' => 'nullable|numeric',
            'price_per_box' => 'nullable|numeric',
            'box_size' => 'nullable|integer|min:1',

            'cost' => 'nullable|numeric',

            'stock_box' => 'required|integer|min:0',
            'min_stock' => 'nullable|integer|min:0',

            'expiry_date' => 'nullable|date',
            'prescription_required' => 'nullable|boolean',

            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',

            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        /**
         * Generate Product Code
         * P001 / P002 / P003
         */
        $last = Products::latest('id')->first();

        if ($last) {
            $number = intval(substr($last->product_code, 1)) + 1;
        } else {
            $number = 1;
        }

        $data['product_code'] = 'P' . str_pad($number, 3, '0', STR_PAD_LEFT);

        /**
         * Upload Image
         */
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

        /**
         * Stock Logic
         */
        $unit = null;

        if (!empty($data['unit_id'])) {
            $unit = Unit::find($data['unit_id']);
        }

        if ($unit && strtolower($unit->name) == 'box') {

            $boxSize = $data['box_size'] ?? 1;
            $data['stock_unit'] = $data['stock_box'] * $boxSize;
        } else {

            $data['stock_unit'] = $data['stock_box'];
            $data['box_size'] = $data['box_size'] ?? 1;
        }
        $product = Products::create($data);

        return apiResponse($product, 200, 'Add product successfully');
    }

    /**
     * Get Product By ID
     */
    public function GetProductById($id)
    {
        $product = Products::with([
            'category',
            'SubCategory',
            'unit'
        ])->findOrFail($id);

        return apiResponse($product, 200, 'Get product successfully');
    }

    /**
     * Update Product
     */
    public function UpdateProduct(Request $request, $id)
    {
        $product = Products::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',

            'category_id' => 'required|exists:categories,id',
            'sub_category_id' => 'nullable|exists:sub_categories,id',

            'unit_id' => 'required|exists:units,id',

            'price_per_unit' => 'nullable|numeric',
            'price_per_box' => 'nullable|numeric',
            'box_size' => 'nullable|integer|min:1',

            'cost' => 'nullable|numeric',

            'stock_box' => 'required|integer|min:0',

            'expiry_date' => 'nullable|date',
            'prescription_required' => 'nullable|boolean',

            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',

            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        /**
         * Replace Image
         */
        if ($request->hasFile('image')) {

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

        /**
         * Stock Logic
         */
        $unit = Unit::find($data['unit_id']);

        if (strtolower($unit->name) == 'box') {

            $boxSize = $data['box_size'] ?? 1;
            $data['stock_unit'] = $data['stock_box'] * $boxSize;
        } else {

            $data['stock_unit'] = $data['stock_box'];
            $data['box_size'] = 1;
        }

        $product->update($data);

        return apiResponse($product, 200, 'Update product successfully');
    }

    /**
     * Delete Product
     */
    public function DeleteProducts($id)
    {
        $product = Products::findOrFail($id);

        if ($product->image_public_id) {
            Cloudinary::destroy($product->image_public_id);
        }

        $product->delete();

        return apiResponse([], 200, 'Delete successfully');
    }

    /**
     * Search Product
     */
    public function SearchProduct($keyword)
    {
        $products = Products::where('name', 'like', "%$keyword%")
            ->orWhere('name_en', 'like', "%$keyword%")
            ->orWhere('product_code', 'like', "%$keyword%")
            ->get();

        return apiResponse($products, 200, 'Search found');
    }
//     public function SearchProduct($keyword)
// {
//     $products = Products::where('id', $keyword)
//         ->orWhere('name', 'like', "%$keyword%")
//         ->orWhere('name_en', 'like', "%$keyword%")
//         ->orWhere('product_code', 'like', "%$keyword%")
//         ->orWhere('barcode', 'like', "%$keyword%")
//         ->get();

//     return apiResponse($products, 200, 'Search found');
// }

    /**
     * Low Stock
     */
    public function LowStock()
    {
        $products = Products::whereColumn('stock_unit', '<=', 'min_stock')->get();

        return apiResponse($products, 200, 'Low stock found');
    }

    /**
     * Expired Product
     */
    public function Expired()
    {
        $products = Products::whereDate('expiry_date', '<=', now())->get();

        return apiResponse($products, 200, 'Expired product found');
    }
}

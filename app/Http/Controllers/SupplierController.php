<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class SupplierController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Get All Suppliers
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        return apiResponse(
            Supplier::latest()->get(),
            200,
            'success'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Store Supplier
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $data = $request->validate([
            'company_name_kh' => 'required|string|max:255',
            'company_name_en' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'note' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'status' => 'nullable|boolean'
        ]);

        if ($request->hasFile('image')) {
            $uploaded = Cloudinary::upload(
                $request->file('image')->getRealPath(),
                [
                    'folder' => 'suppliers'
                ]
            );

            $data['image'] = $uploaded->getSecurePath();
        }

        $last = Supplier::latest('id')->first();
        $number = $last ? $last->id + 1 : 1;

        $data['supplier_code'] = 'S' . str_pad($number, 3, '0', STR_PAD_LEFT);
        $data['status'] = $data['status'] ?? 1;

        $supplier = Supplier::create($data);

        return apiResponse($supplier, 201, 'Supplier created successfully');
    }

    /*
    |--------------------------------------------------------------------------
    | Show Single Supplier
    |--------------------------------------------------------------------------
    */
    public function show($id)
    {
        $supplier = Supplier::findOrFail($id);

        return apiResponse(
            $supplier,
            200,
            'success'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update Supplier
    |--------------------------------------------------------------------------
    */
    public function update(Request $request, $id)
    {
        $supplier = Supplier::findOrFail($id);

        $data = $request->validate([
            'company_name_kh' => 'required|string|max:255',
            'company_name_en' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'note' => 'nullable|string',
            'image' => 'nullable|string',
            'status' => 'nullable|boolean'
        ]);

        $supplier->update($data);

        return apiResponse(
            $supplier,
            200,
            'Supplier updated successfully'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Supplier
    |--------------------------------------------------------------------------
    */
    public function destroy($id)
    {
        $supplier = Supplier::findOrFail($id);

        $supplier->delete();

        return apiResponse(
            null,
            200,
            'Supplier deleted successfully'
        );
    }
}

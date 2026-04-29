<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index()
    {
        $data = Warehouse::latest()->get();
        return apiResponse($data, 200, "Get warehouse success");
    }

    public function store(Request $req)
    {
        $data = $req->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'note' => 'nullable|string|max:255',
            'status' => 'nullable|boolean',
        ]);

        $warehouse = Warehouse::create($data);

        $warehouse->warehouse_code = 'WH' . str_pad($warehouse->id, 3, '0', STR_PAD_LEFT);
        $warehouse->save();

        return apiResponse($warehouse, 201, 'Warehouse created successfully');
    }

    public function update(Request $req, $id)
    {
        $warehouse = Warehouse::findOrFail($id);

        $data = $req->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'note' => 'nullable|string|max:255',
            'status' => 'nullable|boolean',
        ]);

        $warehouse->update($data);

        return apiResponse($warehouse, 200, "Updated successfully");
    }

    public function destroy($id)
    {
        $warehouse = Warehouse::findOrFail($id);
        $warehouse->delete();

        return apiResponse([], 200, "Deleted successfully");
    }
}

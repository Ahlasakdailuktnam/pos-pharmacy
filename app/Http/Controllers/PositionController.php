<?php

namespace App\Http\Controllers;

use App\Models\Position;
use Illuminate\Http\Request;

class PositionController extends Controller
{
      public function index()
    {
        return response()->json(Position::all());
    }

    // ✅ Add position (admin only)
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $position = Position::create($data);

        return response()->json($position, 201);
    }

    // ✅ Update position
    public function update(Request $request, $id)
    {
        $position = Position::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $position->update($data);

        return response()->json($position);
    }

    // ✅ Delete position
    public function destroy($id)
    {
        $position = Position::findOrFail($id);
        $position->delete();

        return response()->json(['message' => 'Deleted']);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
     public function index(Request $request)
    {
        $query = Expense::query();

        // Search
        if ($request->search) {
            $query->where('title', 'like', "%{$request->search}%")
                  ->orWhere('note', 'like', "%{$request->search}%");
        }

        // Filter category
        if ($request->category && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        // Filter status
        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter month
        if ($request->month && $request->month !== 'all') {
            [$year, $month] = explode('-', $request->month);
            $query->whereYear('date', $year)
                  ->whereMonth('date', $month + 1);
        }

        return response()->json($query->latest()->get());
    }

    // POST
    public function store(Request $request)
    {
        $expense = Expense::create($request->all());
        return response()->json($expense);
    }

    // PUT
    public function update(Request $request, $id)
    {
        $expense = Expense::findOrFail($id);
        $expense->update($request->all());
        return response()->json($expense);
    }

    // DELETE
    public function destroy($id)
    {
        Expense::destroy($id);
        return response()->json(['message' => 'Deleted']);
    }

    // Stats (for your dashboard cards)
    public function stats()
    {
        return response()->json([
            'total' => Expense::sum('amount'),
            'paid' => Expense::where('status', 'paid')->sum('amount'),
            'pending' => Expense::where('status', 'pending')->sum('amount'),
            'this_month' => Expense::whereMonth('date', now()->month)
                                   ->whereYear('date', now()->year)
                                   ->sum('amount'),
        ]);
    }
}

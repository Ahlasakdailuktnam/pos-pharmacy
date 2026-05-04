<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\Purchase;
use App\Models\Products;
use App\Models\PurchaseItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class SupplierController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Get All Suppliers
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $query = Supplier::query();


        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('company_name_kh', 'like', '%' . $request->search . '%')
                    ->orWhere('phone', 'like', '%' . $request->search . '%')
                    ->orWhere('supplier_code', 'like', '%' . $request->search . '%');
            });

            return apiResponse(
                $query->latest()->limit(20)->get(),
                200,
                'success'
            );
        }

        //  Case 2: Get ALL (for table or dropdown)
        return apiResponse(
            $query->latest()->get(),
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
    public function dashboard(Request $request)
    {
        $year = $request->year ?? date('Y');
        $month = $request->month ?? date('n');

        $months = [
            'មករា',
            'កុម្ភៈ',
            'មីនា',
            'មេសា',
            'ឧសភា',
            'មិថុនា',
            'កក្កដា',
            'សីហា',
            'កញ្ញា',
            'តុលា',
            'វិច្ឆិកា',
            'ធ្នូ'
        ];
        $monthsShort = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        // 1. Total expense from all purchases
        $totalExpense = Purchase::where('status', 'completed')->sum('grand_total');

        // 2. Current month expense
        $currentMonthExpense = Purchase::where('status', 'completed')
            ->whereYear('purchase_date', $year)
            ->whereMonth('purchase_date', $month)
            ->sum('grand_total');

        // 3. Previous month expense for trend
        $prevMonth = $month == 1 ? 12 : $month - 1;
        $prevYear = $month == 1 ? $year - 1 : $year;
        $prevMonthExpense = Purchase::where('status', 'completed')
            ->whereYear('purchase_date', $prevYear)
            ->whereMonth('purchase_date', $prevMonth)
            ->sum('grand_total');

        $trend = $prevMonthExpense > 0
            ? round(($currentMonthExpense - $prevMonthExpense) / $prevMonthExpense * 100, 1)
            : 0;

        // 4. Total suppliers count
        $totalSuppliers = Supplier::count();

        // 5. Total products count
        $totalProducts = Products::count();

        // 6. Expense by product category (from purchases)
        $categoryExpense = DB::table('purchases')
            ->join('purchase_items', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->join('products', 'purchase_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->where('purchases.status', 'completed')
            ->whereYear('purchases.purchase_date', $year)
            ->whereMonth('purchases.purchase_date', $month)
            ->select('categories.name as category', DB::raw('SUM(purchase_items.line_total) as expense'))
            ->groupBy('categories.id', 'categories.name')
            ->get();

        // 7. Monthly expense trend for chart
        $monthlyTrend = [];
        for ($i = 1; $i <= 12; $i++) {
            $expense = Purchase::where('status', 'completed')
                ->whereYear('purchase_date', $year)
                ->whereMonth('purchase_date', $i)
                ->sum('grand_total');

            $monthlyTrend[] = [
                'month' => $months[$i - 1],
                'expense' => $expense
            ];
        }

        // 8. Top 5 suppliers by purchase amount
        $topSuppliers = Supplier::withCount(['purchases as total_purchased' => function ($q) {
            $q->select(DB::raw('COALESCE(SUM(grand_total), 0)'));
        }])
            ->orderBy('total_purchased', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($supplier) {
                // Get supplier category from their products
                $category = DB::table('products')
                    ->join('categories', 'products.category_id', '=', 'categories.id')
                    ->where('products.supplier_id', $supplier->id)
                    ->select('categories.name')
                    ->first();

                return [
                    'id' => $supplier->id,
                    'name' => $supplier->company_name_kh,
                    'nameEn' => $supplier->company_name_en,
                    'totalPurchased' => $supplier->total_purchased,
                    'category' => $category->name ?? 'ផ្សេងៗ',
                    'avatar' => $supplier->image,
                    'status' => $supplier->status ? 'active' : 'inactive',
                    'contactPerson' => $supplier->contact_person,
                    'phone' => $supplier->phone,
                ];
            });

        // 9. Pie chart data (expense by category)
        $pieChartData = [];
        foreach ($categoryExpense as $item) {
            $pieChartData[] = [
                'name' => $item->category,
                'value' => (float)$item->expense,
                'color' => ['#0D9488', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4'][count($pieChartData) % 7]
            ];
        }

        // 10. Get all suppliers with their purchase summary
        $allSuppliers = Supplier::withCount(['purchases as total_purchased' => function ($q) {
            $q->select(DB::raw('COALESCE(SUM(grand_total), 0)'));
        }])
            ->withCount(['purchases as current_month_amount' => function ($q) use ($year, $month) {
                $q->whereYear('purchase_date', $year)
                    ->whereMonth('purchase_date', $month)
                    ->select(DB::raw('COALESCE(SUM(grand_total), 0)'));
            }])
            ->get()
            ->map(function ($supplier) use ($year, $monthsShort) {
                // Get supplier category
                $category = DB::table('products')
                    ->join('categories', 'products.category_id', '=', 'categories.id')
                    ->where('products.supplier_id', $supplier->id)
                    ->select('categories.name')
                    ->first();

                // Get monthly expense for chart
                $monthlyExpense = [];
                for ($i = 1; $i <= 12; $i++) {
                    $expense = Purchase::where('supplier_id', $supplier->id)
                        ->where('status', 'completed')
                        ->whereYear('purchase_date', $year)
                        ->whereMonth('purchase_date', $i)
                        ->sum('grand_total');

                    $monthlyExpense[$monthsShort[$i - 1]] = $expense;
                }

                // Get total products count from this supplier
                $productCount = Products::where('supplier_id', $supplier->id)->count();

                // Get last order date
                $lastOrder = Purchase::where('supplier_id', $supplier->id)
                    ->where('status', 'completed')
                    ->latest('purchase_date')
                    ->first();

                return [
                    'id' => $supplier->id,
                    'name' => $supplier->company_name_kh,
                    'nameEn' => $supplier->company_name_en,
                    'contactPerson' => $supplier->contact_person,
                    'email' => $supplier->email,
                    'phone' => $supplier->phone,
                    'address' => $supplier->address,
                    'category' => $category->name ?? 'ផ្សេងៗ',
                    'status' => $supplier->status ? 'active' : 'inactive',
                    'totalPurchased' => $supplier->total_purchased,
                    'totalProducts' => $productCount,
                    'lastOrder' => $lastOrder ? $lastOrder->purchase_date : null,
                    'paymentTerms' => 'សាច់ប្រាក់',
                    'rating' => 4.5,
                    'avatar' => $supplier->image,
                    'monthlyExpense' => $monthlyExpense,
                    'currentMonthExpense' => $supplier->current_month_amount,
                ];
            });

        return apiResponse([
            'summary' => [
                'total_expense' => $totalExpense,
                'current_month_expense' => $currentMonthExpense,
                'trend' => $trend,
                'total_suppliers' => $totalSuppliers,
                'total_products' => $totalProducts,
            ],
            'category_expense' => $categoryExpense,
            'monthly_trend' => $monthlyTrend,
            'top_suppliers' => $topSuppliers,
            'suppliers' => $allSuppliers,
            'pie_chart_data' => $pieChartData,
        ], 200, 'Dashboard data retrieved successfully');
    }
}

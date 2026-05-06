<?php

namespace App\Http\Controllers;

use App\Models\Products;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboradController extends Controller
{
    

    /**
     * Get complete product dashboard data for ProductDashboard component
     */
    public function productDashboard()
    {
        $products = Products::with('category')->get();

        $today = now();
        $threeMonthsLater = now()->addMonths(3);

        // 1. Statistics
        $stats = [
            'total_products' => $products->count(),
            'total_stock' => $products->sum('stock_unit'),
            'low_stock' => $products->where('stock_unit', '>', 0)->where('stock_unit', '<=', 50)->count(),
            'out_of_stock' => $products->where('stock_unit', 0)->count(),
            'expiring_soon' => $products->filter(function ($p) use ($today, $threeMonthsLater) {
                return $p->expiry_date && $p->expiry_date >= $today && $p->expiry_date < $threeMonthsLater;
            })->count(),
            'expired' => $products->filter(function ($p) use ($today) {
                return $p->expiry_date && $p->expiry_date < $today;
            })->count(),
        ];

        // 2. Category data for bar chart
        $categoryData = [];
        $categories = $products->groupBy('category.name');

        foreach ($categories as $categoryName => $items) {
            if ($categoryName) {
                $categoryData[] = [
                    'name' => $categoryName,
                    'stock' => $items->sum('stock_unit'),
                    'count' => $items->count(),
                    'lowStock' => $items->where('stock_unit', '>', 0)->where('stock_unit', '<=', 50)->count(),
                ];
            }
        }

        // 3. Stock distribution for pie chart
        $stockDistribution = [
            ['name' => 'ស្តុកល្អ (>100)', 'value' => $products->where('stock_unit', '>', 100)->count(), 'color' => '#10B981'],
            ['name' => 'ស្តុកមធ្យម (51-100)', 'value' => $products->whereBetween('stock_unit', [51, 100])->count(), 'color' => '#F59E0B'],
            ['name' => 'ស្តុកជិតអស់ (1-50)', 'value' => $products->whereBetween('stock_unit', [1, 50])->count(), 'color' => '#EF4444'],
            ['name' => 'អស់ស្តុក (0)', 'value' => $products->where('stock_unit', 0)->count(), 'color' => '#6B7280'],
        ];

        // 4. Expiry distribution for pie chart
        $expiryDistribution = [
            ['name' => 'ល្អ', 'value' => $products->filter(fn($p) => $p->expiry_date && $p->expiry_date >= $threeMonthsLater)->count(), 'color' => '#10B981'],
            ['name' => 'ជិតផុតកំណត់', 'value' => $products->filter(fn($p) => $p->expiry_date && $p->expiry_date >= $today && $p->expiry_date < $threeMonthsLater)->count(), 'color' => '#F59E0B'],
            ['name' => 'ផុតកំណត់', 'value' => $products->filter(fn($p) => $p->expiry_date && $p->expiry_date < $today)->count(), 'color' => '#EF4444'],
        ];

        // 5. Urgent low stock products (stock <= 30)
        $urgentLowStock = $products->where('stock_unit', '>', 0)->where('stock_unit', '<=', 30)
            ->sortBy('stock_unit')
            ->values()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'category' => $product->category->name ?? 'ផ្សេងៗ',
                    'stock' => $product->stock_unit,
                    'expiry' => $product->expiry_date,
                ];
            });

        // 6. Expiring soon products
        $expiringSoonProducts = $products->filter(fn($p) => $p->expiry_date && $p->expiry_date >= $today && $p->expiry_date < $threeMonthsLater)
            ->sortBy('expiry_date')
            ->values()
            ->map(function ($product) use ($today) {
                $daysLeft = ceil(now()->diffInDays($product->expiry_date));
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'category' => $product->category->name ?? 'ផ្សេងៗ',
                    'stock' => $product->stock_unit,
                    'expiry' => $product->expiry_date,
                    'days_left' => $daysLeft,
                ];
            });
        // Out of stock products
        $outOfStockProducts = $products->where('stock_unit', 0)
            ->values()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'category' => $product->category->name ?? 'ផ្សេងៗ',
                    'stock' => $product->stock_unit,
                ];
            });

        // Top selling products (from order_items)
        $topSelling = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('units', 'products.unit_id', '=', 'units.id')
            ->select(
                'products.id',
                'products.name',
                'products.category_id',
                'units.name as unit_name',
                DB::raw('SUM(order_items.quantity) as total_sold')
            )
            ->groupBy('products.id', 'products.name', 'products.category_id', 'units.name')
            ->orderBy('total_sold', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                $category = \App\Models\Category::find($item->category_id);
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'category' => $category->name ?? 'ផ្សេងៗ',
                    'sales' => $item->total_sold,
                    'unit' => $item->unit_name ?? 'ដុំ',
                ];
            });

        return apiResponse([
            'stats' => $stats,
            'category_data' => $categoryData,
            'stock_distribution' => $stockDistribution,
            'expiry_distribution' => $expiryDistribution,
            'urgent_low_stock' => $urgentLowStock,
            'expiring_soon_products' => $expiringSoonProducts,
            'top_selling' => $topSelling,
            'out_of_stock_products' => $outOfStockProducts,

        ], 200, 'Product dashboard data retrieved successfully');
    }
}

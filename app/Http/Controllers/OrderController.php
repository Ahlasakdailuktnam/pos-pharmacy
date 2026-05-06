<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use App\Models\Products;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        // ✅ VALIDATE
        $request->validate([
            'payment_method' => 'required|in:cash,card,qr',
            'subtotal' => 'required|numeric',
            'total' => 'required|numeric',
            'items' => 'required|array|min:1',
        ]);

        DB::beginTransaction();

        try {

            // ✅ CHECK STOCK BEFORE SAVE
            foreach ($request->items as $item) {
                $product = Products::find($item['product_id']);

                if (!$product || $product->stock_unit < $item['quantity']) {
                    return apiResponse(null, 400, "ស្តុកមិនគ្រប់គ្រាន់: " . ($product->name ?? ''));
                }
            }

            // ✅ SAVE CUSTOMER (OPTIONAL) AND GET CUSTOMER ID
            $customerId = null;
            if ($request->customer_phone) {
                $customer = Customer::updateOrCreate(
                    ['phone' => $request->customer_phone],
                    ['name' => $request->customer_name]
                );
                $customerId = $customer->id; //
            }

            // ✅ CREATE ORDER (ADD customer_id)
            $order = Order::create([
                'order_number' => 'ORD-' . time(),

                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'customer_id' => $customerId, 

                'payment_method' => $request->payment_method,

                'cash_received' => $request->cash_received,
                'change_amount' => $request->change_amount,

                'bank_name' => $request->bank_name,
                'payment_phone' => $request->payment_phone,

                'subtotal' => $request->subtotal,
                'discount' => $request->discount ?? 0,
                 'discount_percent' => $request->discount_percent ?? 0,
                'total' => $request->total,
                'user_id' => Auth::id(), 
            ]);

            // ✅ SAVE ITEMS + REDUCE STOCK
            foreach ($request->items as $item) {

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'total' => $item['total'],
                ]);

                // 🔥 REDUCE STOCK
                $product = Products::find($item['product_id']);

                $qty = $item['quantity'];

                // reduce unit
                $product->decrement('stock_unit', $qty);

                // reduce box (if has box)
                if ($product->box_size > 1) {
                    $boxUsed = floor($qty / $product->box_size);

                    if ($boxUsed > 0) {
                        $product->decrement('stock_box', $boxUsed);
                    }
                }
            }

            DB::commit();

            return apiResponse([
                'order_id' => $order->id
            ], 200, 'បញ្ចប់ការលក់ជោគជ័យ');

        } catch (\Exception $e) {

            DB::rollBack();

            return apiResponse([
                'error' => $e->getMessage()
            ], 500, 'មានបញ្ហា');
        }
    }
     public function todaySales()
    {
        $orders = Order::with(['items', 'user'])
            ->whereDate('created_at', today())
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();
        
        $summary = [
            'total_orders' => $orders->count(),
            'total_sales' => $orders->sum('total'),
            'total_items' => $orders->sum(function($order) {
                return $order->items->sum('quantity');
            }),
            'total_discount' => $orders->sum('discount'),
        ];
        
        return apiResponse([
            'orders' => $orders,
            'summary' => $summary,
        ], 200, 'Today sales retrieved successfully');
    }

    /**
     * Get sales history for staff (filter by date)
     */
    public function staffSales(Request $request)
    {
        $query = Order::with(['items', 'user'])
            ->where('user_id', Auth::id());
        
        // Filter by date
        if ($request->date) {
            $query->whereDate('created_at', $request->date);
        } else {
            $query->whereDate('created_at', today());
        }
        
        // Filter by date range
        if ($request->start_date && $request->end_date) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }
        
        $orders = $query->orderBy('created_at', 'desc')->get();
        
        $summary = [
            'total_orders' => $orders->count(),
            'total_sales' => $orders->sum('total'),
            'total_items' => $orders->sum(function($order) {
                return $order->items->sum('quantity');
            }),
            'total_discount' => $orders->sum('discount'),
        ];
        
        return apiResponse([
            'orders' => $orders,
            'summary' => $summary,
        ], 200, 'Sales history retrieved successfully');
    }

    /**
     * Get all sales for admin (all users, all dates)
     */
    public function allSales(Request $request)
    {
        $query = Order::with(['items', 'user']);
        
        // Filter by date
        if ($request->date) {
            $query->whereDate('created_at', $request->date);
        }
        
        // Filter by date range
        if ($request->start_date && $request->end_date) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }
        
        // Filter by user
        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }
        
        $orders = $query->orderBy('created_at', 'desc')->get();
        
        $summary = [
            'total_orders' => $orders->count(),
            'total_sales' => $orders->sum('total'),
            'total_items' => $orders->sum(function($order) {
                return $order->items->sum('quantity');
            }),
            'total_discount' => $orders->sum('discount'),
        ];
        
        return apiResponse([
            'orders' => $orders,
            'summary' => $summary,
        ], 200, 'All sales retrieved successfully');
    }
    /**
 * Get sales statistics for admin dashboard
 */
public function salesStats(Request $request)
{
    $today = today();
    $thisMonth = now()->startOfMonth();
    $lastMonth = now()->subMonth()->startOfMonth();
    
    // Today's sales
    $todaySales = Order::whereDate('created_at', $today)->sum('total');
    $todayOrders = Order::whereDate('created_at', $today)->count();
    
    // This month sales
    $monthSales = Order::whereMonth('created_at', now()->month)->sum('total');
    $monthOrders = Order::whereMonth('created_at', now()->month)->count();
    
    // Last month sales
    $lastMonthSales = Order::whereMonth('created_at', now()->subMonth()->month)->sum('total');
    
    // Calculate growth
    $growth = $lastMonthSales > 0 
        ? round(($monthSales - $lastMonthSales) / $lastMonthSales * 100, 1) 
        : 0;
    
    // Sales by payment method
    $paymentMethodStats = [
        'cash' => (float) Order::where('payment_method', 'cash')->sum('total'),
        'card' => (float) Order::where('payment_method', 'card')->sum('total'),
        'qr' => (float) Order::where('payment_method', 'qr')->sum('total'),
    ];
    
    // Daily sales for chart (last 7 days)
    $dailySales = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = now()->subDays($i);
        $dailySales[] = [
            'date' => $date->format('Y-m-d'),
            'sales' => (float) Order::whereDate('created_at', $date)->sum('total'),
            'orders' => (int) Order::whereDate('created_at', $date)->count(),
        ];
    }
    
    return response()->json([
        'success' => true,
        'data' => [
            'today' => [
                'sales' => $todaySales,
                'orders' => $todayOrders,
            ],
            'month' => [
                'sales' => $monthSales,
                'orders' => $monthOrders,
            ],
            'growth' => $growth,
            'payment_methods' => $paymentMethodStats,
            'daily_sales' => $dailySales,
        ],
        'message' => 'Sales statistics retrieved successfully'
    ]);

    }

    /**
     * Get single order details
     */
    public function showOrder($id)
    {
        $order = Order::with(['items', 'user'])->findOrFail($id);
        return apiResponse($order, 200, 'Order retrieved successfully');
    }
}
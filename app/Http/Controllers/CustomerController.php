<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query();
        
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%");
            });
        }
        
        $customers = $query->latest()->get();
        
        // Add calculated fields
        $customers->transform(function($customer) {
            $customer->total_spent = (float) $customer->orders()->sum('total');
            $customer->total_orders = (int) $customer->orders()->count();
            $customer->last_order_date = $customer->orders()->latest()->first()?->created_at;
            return $customer;
        });
        
        return apiResponse($customers, 200, 'success');
    }
    
    public function stats()
    {
        $totalCustomers = Customer::count();
        
        // Use orders with customer_id
        $totalSpent = (float) Order::whereNotNull('customer_id')->sum('total');
        $totalOrders = (int) Order::whereNotNull('customer_id')->count();
        $avgSpent = $totalCustomers > 0 ? round($totalSpent / $totalCustomers, 2) : 0;
        
        return apiResponse([
            'total_customers' => $totalCustomers,
            'total_spent' => $totalSpent,
            'total_orders' => $totalOrders,
            'avg_spent' => $avgSpent,
        ], 200, 'success');
    }
    
    public function show($id)
    {
        $customer = Customer::with('orders')->findOrFail($id);
        
        $customer->total_spent = (float) $customer->orders()->sum('total');
        $customer->total_orders = (int) $customer->orders()->count();
        $customer->last_order_date = $customer->orders()->latest()->first()?->created_at;
        
        return apiResponse($customer, 200, 'success');
    }
     public function getByPhone($phone)
    {
        $customer = Customer::where('phone', $phone)->first();
        
        if (!$customer) {
            return apiResponse([
                'exists' => false,
                'discount_rate' => 0,
                'tier' => 'Bronze',
                'message' => 'អតិថិជនថ្មី'
            ], 200, 'Customer not found');
        }
        
        // Calculate total spent from all orders
        $totalSpent = (float) $customer->orders()->sum('total');
        $totalOrders = (int) $customer->orders()->count();
        
        // Get discount rate based on total spent
        $discountRate = $this->getDiscountRate($totalSpent);
        $tier = $this->getTier($totalSpent);
        
        return apiResponse([
            'exists' => true,
            'id' => $customer->id,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'total_spent' => $totalSpent,
            'total_orders' => $totalOrders,
            'tier' => $tier,
            'discount_rate' => $discountRate,
            'message' => "សូមស្វាគមន៍! អ្នកទទួលបានបញ្ចុះតម្លៃ {$discountRate}% ({$tier})"
        ], 200, 'Customer found');
    }
    
    /**
     * Get discount rate based on total spending
     */
    private function getDiscountRate($totalSpent)
    {
        if ($totalSpent >= 3000) return 15;  // Diamond: 15%
        if ($totalSpent >= 2000) return 10;  // Platinum: 10%
        if ($totalSpent >= 1000) return 5;   // Gold: 5%
        if ($totalSpent >= 500) return 3;    // Silver: 3%
        return 0;                            // Bronze: 0%
    }
    private function getTier($totalSpent)
    {
        if ($totalSpent >= 3000) return 'Diamond';
        if ($totalSpent >= 2000) return 'Platinum';
        if ($totalSpent >= 1000) return 'Gold';
        if ($totalSpent >= 500) return 'Silver';
        return 'Bronze';
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\Products;
use Illuminate\Http\Request;

class DashboradController extends Controller
{
    public function index(){
        return apiResponse(
            [
                'total_products'=> Products::count(),
                'total_stock'=> Products::sum('stock_unit'),
                'low_stock'=> Products::where('stock_unit', '<=' , 10)->where('stock_unit', '>', 0)->count(),
                'out_of_stock'=> Products::where('stock_unit',0)->count(),
                'expired'=> Products::whereDate('expiry_date', '<' , now())->count(),
                'expiring_soon'=>Products::whereBetween('expiry_date',[now(), now()->addMonths(2)])->count(),
            ], 200, 'dashboard sucess'
        );
    }
}

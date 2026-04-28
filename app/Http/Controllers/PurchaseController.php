<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Products;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function index()
    {
        $data = Purchase::with([
            'supplier',
            'warehouse',
            'items.product'
        ])->latest()->get();

return apiResponse($data, 200, 'Get purchase successfully');    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {

            $purchase = Purchase::create([
                'purchase_code'   => 'PUR-' . time(),
                'supplier_id'     => $request->supplier_id,
                'warehouse_id'    => $request->warehouse_id,
                'invoice_number'  => $request->invoice_number,
                'purchase_date'   => $request->purchase_date,
                'expected_date'   => $request->expected_date,
                'payment_method'  => $request->payment_method,
                'payment_status'  => $request->payment_status,
                'subtotal'        => $request->subtotal,
                'discount_total'  => $request->discount_total,
                'tax_total'       => $request->tax_total,
                'grand_total'     => $request->grand_total,
                'note'            => $request->note,
                'status'          => 'completed',
            ]);

            foreach ($request->items as $item) {

                PurchaseItem::create([
                    'purchase_id'       => $purchase->id,
                    'product_id'        => $item['product_id'],
                    'qty'               => $item['qty'],
                    'unit_cost'         => $item['unit_cost'],
                    'discount_percent'  => $item['discount_percent'],
                    'tax_percent'       => $item['tax_percent'],
                    'line_total'        => $item['line_total'],
                ]);

                $product = Products::find($item['product_id']);

                if ($product) {
                    $product->stock_unit += $item['qty'];
                    $product->cost = $item['unit_cost'];
                    $product->save();
                }
            }

            DB::commit();

           return apiResponse($purchase, 200, 'Purchase created successfully');

        } catch (\Exception $e) {

            DB::rollBack();

           return apiResponse($e->getMessage(), 500, 'Something went wrong');
        }
    }

    public function show($id)
    {
        $data = Purchase::with([
            'supplier',
            'warehouse',
            'items.product'
        ])->findOrFail($id);

return apiResponse($data, 200, 'Get purchase by id successfully');    }

    public function destroy($id)
    {
        Purchase::findOrFail($id)->delete();

       return apiResponse([], 200, 'Delete purchase successfully');
    }
}
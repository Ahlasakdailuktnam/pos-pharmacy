<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Products;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    /**
     * Get All Purchases
     */
    public function index()
    {
        $data = Purchase::with([
            'supplier',
            'warehouse',
            'items.product'
        ])->latest()->get();

        return apiResponse($data, 200, 'Get purchase successfully');
    }

    /**
     * Create Purchase + Update Stock
     */
    public function store(Request $request)
    {
        $request->validate([
            'supplier_id'   => 'required|exists:suppliers,id',
            'warehouse_id'  => 'required|exists:warehouses,id',
            'purchase_date' => 'required|date',
            'items'         => 'required|array|min:1',
            'payment_status'=> 'required|in:pending,partial,paid',
        ]);

        DB::beginTransaction();

        try {

            /**
             *  HANDLE PAYMENT LOGIC
             */
            $paidAmount = 0;

            if ($request->payment_status === 'paid') {
                $paidAmount = $request->grand_total;
            } elseif ($request->payment_status === 'partial') {
                $paidAmount = $request->paid_amount ?? 0;
            } else {
                $paidAmount = 0;
            }

            /**
             *  VALIDATION EXTRA
             */
            if ($paidAmount > $request->grand_total) {
                return apiResponse([], 400, 'Paid amount cannot be greater than total');
            }

            /**
             * CREATE PURCHASE
             */
            $purchase = Purchase::create([
                'purchase_code'   => 'PUR-' . date('Ymd') . '-' . rand(100,999),
                'supplier_id'     => $request->supplier_id,
                'warehouse_id'    => $request->warehouse_id,
                'invoice_number'  => $request->invoice_number,
                'purchase_date'   => $request->purchase_date,
                'expected_date'   => $request->expected_date,
                'payment_method'  => $request->payment_method,
                'payment_status'  => $request->payment_status,
                'paid_amount'     => $paidAmount, 
                'subtotal'        => $request->subtotal,
                'discount_total'  => $request->discount_total,
                'tax_total'       => $request->tax_total,
                'grand_total'     => $request->grand_total,
                'note'            => $request->note,
                'status'          => 'completed',
            ]);

            /**
             * CREATE ITEMS + UPDATE STOCK
             */
            foreach ($request->items as $item) {

                PurchaseItem::create([
                    'purchase_id'       => $purchase->id,
                    'product_id'        => $item['product_id'],
                    'qty'               => $item['qty'],
                    'unit_cost'         => $item['unit_cost'],
                    'discount_percent'  => $item['discount_percent'] ?? 0,
                    'tax_percent'       => $item['tax_percent'] ?? 0,
                    'line_total'        => $item['line_total'],
                ]);

                $product = Products::find($item['product_id']);

                if ($product) {

                    if ($product->box_size > 1) {
                        $product->stock_box += $item['qty'];
                        $product->stock_unit += ($item['qty'] * $product->box_size);
                    } else {
                        $product->stock_unit += $item['qty'];
                    }

                    // update latest cost
                    $product->cost = $item['unit_cost'];

                    $product->save();
                }
            }

            DB::commit();

            $purchase->load([
                'supplier',
                'warehouse',
                'items.product'
            ]);

            return apiResponse($purchase, 200, 'Purchase created successfully');

        } catch (\Exception $e) {

            DB::rollBack();

            return apiResponse($e->getMessage(), 500, 'Something went wrong');
        }
    }

    /**
     * Get Purchase By ID
     */
    public function show($id)
    {
        $data = Purchase::with([
            'supplier',
            'warehouse',
            'items.product'
        ])->findOrFail($id);

        return apiResponse($data, 200, 'Get purchase by id successfully');
    }

    /**
     * Delete Purchase + Reverse Stock
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {

            $purchase = Purchase::with('items')->findOrFail($id);

            foreach ($purchase->items as $item) {

                $product = Products::find($item->product_id);

                if ($product) {

                    if ($product->box_size > 1) {
                        $product->stock_box -= $item->qty;
                        $product->stock_unit -= ($item['qty'] * $product->box_size);
                    } else {
                        $product->stock_unit -= $item->qty;
                    }

                    $product->save();
                }
            }

            $purchase->items()->delete();
            $purchase->delete();

            DB::commit();

            return apiResponse([], 200, 'Delete purchase successfully');

        } catch (\Exception $e) {

            DB::rollBack();

            return apiResponse($e->getMessage(), 500, 'Something went wrong');
        }
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Products;
use App\Models\PurchasePayment;
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
            'payment_status' => 'required|in:pending,partial,paid',
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
                'purchase_code'   => 'PUR-' . date('Ymd') . '-' . rand(100, 999),
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








    /**
     * Get payment history of a specific purchase
     */
    public function getPayments($id)
    {
        $purchase = Purchase::with(['payments.createdBy'])->findOrFail($id);

        return apiResponse([
            'payments' => $purchase->payments,
            'summary' => [
                'total_paid' => $purchase->paid_amount,
                'remaining' => $purchase->grand_total - $purchase->paid_amount,
                'grand_total' => $purchase->grand_total,
                'payment_status' => $purchase->payment_status
            ]
        ], 200, 'Get payment history successfully');
    }

    /**
     * Add new payment to purchase
     */

    public function addPayment(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,bank_transfer', 
            'payment_date' => 'required|date',
            'notes' => 'nullable|string|max:500'
        ]);

        DB::beginTransaction();

        try {
            $purchase = Purchase::findOrFail($id);
            $remaining = $purchase->grand_total - $purchase->paid_amount;

            if ($request->amount > $remaining) {
                return apiResponse([], 400, 'ទឹកប្រាក់លើសពីចំនួនដែលនៅសល់ (នៅសល់: ' . number_format($remaining, 2) . ')');
            }

            // reference_no will be auto-generated by the model
            $payment = PurchasePayment::create([
                'purchase_id' => $purchase->id,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'payment_date' => $request->payment_date,
                'notes' => $request->notes,
                'created_by' => auth()->id()
            ]);

            // Update purchase
            $newPaidAmount = $purchase->paid_amount + $request->amount;

            if ($newPaidAmount >= $purchase->grand_total) {
                $paymentStatus = 'paid';
            } elseif ($newPaidAmount > 0) {
                $paymentStatus = 'partial';
            } else {
                $paymentStatus = 'pending';
            }

            $purchase->update([
                'paid_amount' => $newPaidAmount,
                'payment_status' => $paymentStatus
            ]);

            DB::commit();

            return apiResponse([
                'purchase' => $purchase->load(['payments', 'supplier']),
                'payment' => $payment->load('createdBy')
            ], 200, 'កត់ត្រាការបង់ប្រាក់ដោយជោគជ័យ');
        } catch (\Exception $e) {
            DB::rollBack();
            return apiResponse($e->getMessage(), 500, 'មានបញ្ហាកើតឡើង: ' . $e->getMessage());
        }
    }

    /**
     * Delete a payment (reverse transaction)
     */
    public function deletePayment($purchaseId, $paymentId)
    {
        DB::beginTransaction();

        try {
            $payment = PurchasePayment::where('purchase_id', $purchaseId)
                ->where('id', $paymentId)
                ->firstOrFail();

            $purchase = $payment->purchase;

            $newPaidAmount = $purchase->paid_amount - $payment->amount;

            if ($newPaidAmount <= 0) {
                $paymentStatus = 'pending';
                $newPaidAmount = 0;
            } elseif ($newPaidAmount >= $purchase->grand_total) {
                $paymentStatus = 'paid';
            } else {
                $paymentStatus = 'partial';
            }

            $purchase->update([
                'paid_amount' => $newPaidAmount,
                'payment_status' => $paymentStatus
            ]);

            $payment->delete();

            DB::commit();

            return apiResponse($purchase->load('payments'), 200, 'លុបកំណត់ត្រាការបង់ប្រាក់ដោយជោគជ័យ');
        } catch (\Exception $e) {
            DB::rollBack();

            return apiResponse($e->getMessage(), 500, 'មានបញ្ហាកើតឡើង: ' . $e->getMessage());
        }
    }
    /**
     * Get pending and partial purchases only (for payment management page)
     */
    public function getPendingPurchases()
    {
        $purchases = Purchase::with(['supplier', 'warehouse', 'items'])
            ->whereIn('payment_status', ['pending', 'partial'])
            ->orderBy('purchase_date', 'desc')
            ->get();

        return apiResponse($purchases, 200, 'Get pending purchases successfully');
    }
    /**
     * Get all payments from all purchases
     */
    public function getAllPayments()
    {
        $payments = PurchasePayment::with(['purchase.supplier', 'createdBy'])
            ->orderBy('payment_date', 'desc')
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'purchase_id' => $payment->purchase_id,
                    'purchase_code' => $payment->purchase->purchase_code ?? null,
                    'supplier_name' => $payment->purchase->supplier->company_name_kh ??
                        $payment->purchase->supplier->company_name_en ?? null,
                    'amount' => $payment->amount,
                    'payment_method' => $payment->payment_method,
                    'payment_date' => $payment->payment_date,
                    'reference_no' => $payment->reference_no,
                    'notes' => $payment->notes,
                    'purchase_status' => $payment->purchase->payment_status ?? null,
                    'created_by_name' => $payment->createdBy->name ?? null,
                    'created_at' => $payment->created_at,
                ];
            });

        return apiResponse($payments, 200, 'Get all payments successfully');
    }
}

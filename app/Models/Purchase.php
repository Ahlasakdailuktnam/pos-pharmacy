<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $fillable = [
        'purchase_code',
        'supplier_id',
        'warehouse_id',
        'invoice_number',
        'purchase_date',
        'expected_date',
        'payment_method',
        'payment_status',
        'paid_amount',
        'subtotal',
        'discount_total',
        'tax_total',
        'grand_total',
        'note',
        'status'
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }
    public function payments()
    {
        return $this->hasMany(PurchasePayment::class);
    }

    // Optional helper methods
    public function getRemainingAttribute(): float
    {
        return $this->grand_total - $this->paid_amount;
    }

    public function getPaidPercentageAttribute(): float
    {
        if ($this->grand_total <= 0) return 0;
        return ($this->paid_amount / $this->grand_total) * 100;
    }
}

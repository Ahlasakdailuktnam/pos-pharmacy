<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
     protected $fillable = [
        'order_number',
        'customer_id',       
        'customer_name',
        'customer_phone',
        'payment_method',
        'cash_received',
        'change_amount',
        'bank_name',
        'payment_phone',
        'subtotal',
        'discount_percent',
        'discount',
        'total',
        'user_id'
    ];

    //  Order has many items
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    //  Order belongs to user (staff)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
     public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}

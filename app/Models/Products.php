<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Products extends Model
{
    protected $fillable = [
         'name',
        'name_en',
        'category_id',
        'sub_category_id',
        'supplier_id',
        'unit_id',
        'barcode',
        'price_per_unit',
        'price_per_box',
        'box_size',
        'cost',
        'stock_box',
        'stock_unit',
        'min_stock',
        'expiry_date',
        'prescription_required',
        'description',
        'location',
        'image',
        'product_code',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
    public function SubCategory()
    {
        return $this->belongsTo(SubCategory::class, 'sub_category_id');
    }
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
    public function supplier(){
        return $this->belongsTo(Supplier::class);
    }
    public function purchaseItems()
{
    return $this->hasMany(PurchaseItem::class, 'product_id');
}
}

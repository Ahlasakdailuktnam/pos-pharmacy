<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Products extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_en',
        'category_id',
        'sub_category_id',
        'unit',
        'price_per_unit',
        'price_per_box',
        'box_size',
        'cost',
        'stock_box',
        'stock_unit',
        'expiry_date',
        'manufacturer',
        'prescription_required',
        'description',
        'location',
        'image',
        'image_public_id',
    ];

    public function category(){
        return $this->belongsTo(Category::class,'category_id');
    }
    public function SubCategory(){
        return $this->belongsTo(SubCategory::class,'sub_category_id');
    }
}

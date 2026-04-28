<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_code',
        'company_name_kh',
        'company_name_en',
        'contact_person',
        'phone',
        'email',
        'address',
        'note',
        'image',
        'status',
    ];

    public function products()
    {
        return $this->hasMany(Products::class);
    }
    public function purchases()
{
    return $this->hasMany(Purchase::class);
}
}
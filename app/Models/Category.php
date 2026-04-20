<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name'];
    public function products(){
        return $this-> hasMany(Products::class, 'category_id');
    }
    public function SubCategory(){
        return $this-> hasMany(SubCategory::class, 'category_id');
    }
}

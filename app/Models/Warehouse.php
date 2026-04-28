<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $fillable = [
        'warehouse_code',
        'name',
        'location',
        'note',
        'status'
    ];

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }
}
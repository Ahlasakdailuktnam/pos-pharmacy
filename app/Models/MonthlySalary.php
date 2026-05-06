<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlySalary extends Model
{
    protected $fillable = [
        'staff_detail_id', 'year', 'month', 'base_salary', 'allowance',
        'deduction', 'bonus', 'total', 'status', 'paid_date'
    ];

    protected $casts = [
        'paid_date' => 'date',
    ];

    public function staffDetail()
    {
        return $this->belongsTo(StaffDetail::class);
    }
}
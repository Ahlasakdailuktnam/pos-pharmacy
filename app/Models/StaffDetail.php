<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffDetail extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'gender',
        'phone',
        'address',
        'employee_id',
        'contract_duration',
        'position_id',
        'work_type',
        'join_date',
        'base_salary',
        'allowance',
        'cv_file',
        'emergency_name',
        'emergency_phone',
        'status'
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationship to Position
    public function position()
    {
        return $this->belongsTo(Position::class);
    }
    public function monthlySalaries()
    {
        return $this->hasMany(MonthlySalary::class);
    }

    public function getMonthlySalary($year, $month)
    {
        return $this->monthlySalaries()
            ->where('year', $year)
            ->where('month', $month)
            ->first();
    }
}

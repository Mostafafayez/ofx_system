<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlySalary extends Model
{
    protected $fillable = [
        'user_id',
        'month',
        'year',
        'total_sales',
        'net_salary',
        'bonus_amount'
    ];
}

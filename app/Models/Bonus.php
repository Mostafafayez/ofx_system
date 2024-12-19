<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bonus extends Model
{
    protected $fillable = [
        'service_id',
        'department_id',
        'target',
        'bonus_amount',
        'bonus_percentage',
        'status',
        'valid_month',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}

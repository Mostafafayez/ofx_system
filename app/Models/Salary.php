<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Salary extends Model
{
    use HasFactory;

    // Fillable fields for mass assignment
    protected $fillable = [
        'user_id',
        'base_salary',
        'target_percentage',
        'target',
        'commission_percentage',

    ];

    // The table associated with the model
    protected $table = 'salaries';

    // Relationship: A salary belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Accessor to calculate net salary
    public function getNetSalaryAttribute()
    {
        return $this->base_salary + $this->commission;
    }

    // Mutator to ensure commission and base salary are stored correctly
    public function setBaseSalaryAttribute($value)
    {
        $this->attributes['base_salary'] = number_format($value, 2, '.', '');
    }

    public function setCommissionAttribute($value)
    {
        $this->attributes['commission'] = number_format($value, 2, '.', '');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    public function contracts()
    {
        return $this->belongsToMany(Contract::class, 'contract_services', 'service_id', 'contract_id')
            ->using(ContractService::class)
            ->withPivot('note','price')
            ->withTimestamps();
    }

    // public function servicable()
    // {
    //     return $this->morphTo();
    // }




    public function layouts()
    {
        return $this->hasMany(Layout::class);
    }
}


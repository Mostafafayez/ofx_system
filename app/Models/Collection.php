<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Collection extends Model
{
    use HasFactory,SoftDeletes;

    protected $fillable = ['contract_service_id', 'amount', 'date', 'status','is_approval','proof_of_payment'];

    public function contractService()
    {
        return $this->belongsTo(ContractService::class);
    }

    public function salesEmployee()
    {
        return $this->hasManyThrough(
            User::class,
            Contract::class,
            'id',
            'id',
            'contract_service_id',
            'sales_employee_id'
        );
    }




    

}





<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Contract extends Model
{
    use HasFactory,SoftDeletes;

    protected $fillable = ['serial_num', 'sales_employee_id', 'client_id','status'];



    public function salesEmployee()
    {
        return $this->belongsTo(User::class, 'sales_employee_id');
    }
    
    public function services()
    {
        return $this->belongsToMany(Service::class, 'contract_services', 'contract_id', 'service_id')
            ->using(ContractService::class)
            ->withPivot('note','price')
            ->withTimestamps();
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }


    public function collections()
    {
        return $this->hasManyThrough(Collection::class, ContractService::class, 'contract_id', 'contract_service_id');
    }


    public function notes()
    {
        return $this->morphMany(Note::class, 'notable');
    }


    public function contractServiceLayouts()
    {
        return $this->hasManyThrough(ContractServiceLayout::class, ContractService::class, 'contract_id', 'contract_service_id');
    }






}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ContractService extends Pivot
{

    protected $table ='contract_services';
    protected $fillable = ['contract_id', 'service_id', 'note','price'];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function layouts()
    {
        return $this->hasMany(ContractServiceLayout::class,'contract_service_id');
    }

    public function collections()
{
    return $this->hasMany(Collection::class, 'contract_service_id', 'id');
}

}


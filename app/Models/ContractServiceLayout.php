<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
class ContractServiceLayout extends Pivot
{
    use HasFactory;
    protected $table ='contract_service_layouts';
    protected $fillable = ['contract_service_id', 'layout_id', 'answer'];

    public function contractService()
    {
        return $this->belongsTo(ContractService::class);
    }

    public function layout()
    {
        return $this->belongsTo(Layout::class);
    }
}

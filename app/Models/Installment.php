<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Installment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['liability_id', 'user_id', 'amount', 'due_date', 'status'];

  
    public function liability()
    {
        return $this->belongsTo(Liability::class);
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}




<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['sales_id','company_name','client_name', 'email', 'phone', 'status','from_where'];

    protected $casts = [
        'phone' => 'array',
    ];

    public function sales()
    {
        return $this->belongsTo(User::class, 'sales_id');
    }

    public function followups()
    {
        return $this->hasMany(FollowUp::class);
    }

    public function offers()
    {
        return $this->hasMany(Offer::class);
    }

    public function notes()
    {
        return $this->morphMany(Note::class, 'notable');
    }
}


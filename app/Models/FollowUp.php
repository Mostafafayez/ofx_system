<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FollowUp extends Model
{
    use HasFactory;
protected $table ='followups';
    protected $fillable = ['lead_id', 'details', 'followed_date','status'];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }


    public function notes()
    {
        return $this->morphMany(Note::class, 'notable');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    use HasFactory;

    protected $fillable = ['lead_id', 'offer_path','description','valid_until'];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function notes()
    {
        return $this->morphMany(Note::class, 'notable');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'teamleader_id','service_id','branch'];


    public function users()
    {
        return $this->hasMany(User::class);
    }


    public function managers()
    {
        return $this->belongsToMany(User::class, 'manager_team', 'team_id', 'user_id')->withTimestamps();
    }

    public function contracts()
    {
        return $this->hasManyThrough(Contract::class, User::class, 'team_id', 'sales_employee_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function tasksAssigned()
    {
        return $this->morphMany(Task::class, 'assigned');
    }
    public function notes()
    {
        return $this->morphMany(Note::class, 'notable');
    }

    public function teamLeader()
    {
        return $this->belongsTo(User::class, 'teamleader_id');
    }


    // public function department()
    // {
    //     return $this->belongsTo(Department::class, 'department_id');
    // }




}

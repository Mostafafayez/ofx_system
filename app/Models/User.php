<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable,SoftDeletes, HasRoles, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */


     protected function getDefaultGuardName(): string { return 'sanctum'; }
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'birth_date',
        'team_id',
        'department_id',
        'National_id',


    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }


    public function contracts()
    {
        return $this->hasMany(Contract::class, 'sales_employee_id');
    }


    public function teams()//for manager
    {
        return $this->belongsToMany(Team::class, 'manager_team', 'user_id', 'team_id');
    }

    public function team()//team
    {
        return $this->belongsTo(Team::class);
    }

    public function leader()
    {
        return $this->hasOne(Team::class, 'teamleader_id');
    }


    public function leads_notes(): HasManyThrough
    {
        return $this->hasManyThrough(Note::class, Lead::class,'sales_id','notable_id');
    }


    public function contracts_notes(): HasManyThrough
    {
        return $this->hasManyThrough(Note::class, Contract::class,'sales_employee_id','notable_id');
    }
    // contracts_tasks

    public function contracts_tasks(): HasManyThrough
    {
        return $this->hasManyThrough(Note::class, Task::class,'assigned_id','notable_id');
    }




    public function department()
    {
        return $this->belongsTo(Department::class);
    }



    public function services()
    {
        return $this->morphMany(Service::class, 'servicable');
    }
    public function notes()
    {
        return $this->morphMany(Note::class, 'notable');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'team_leader_id');
    }

    public function tasksAssigned()
    {
        return $this->morphMany(Task::class, 'assigned');
    }

    public function salaries()
    {
        return $this->hasMany(Salary::class);
    }

    public function leads()
    {
        return $this->hasMany(Lead::class, 'sales_id');
    }

    public function liability()
    {
        return $this->hasMany(Liability::class);
    }

    public function installments()
    {
        return $this->hasMany(Installment::class);
    }



public function monthlySalaries()
{
    return $this->hasMany(MonthlySalary::class);
}



    // public function collections(): HasManyThrough
    // {
    //     return $this->hasManyThrough(
    //         Collection::class,      // Final target model
    //         Contract::class,        // Intermediate model
    //         'sales_employee_id',    // Foreign key on Contract (to connect with User)
    //         'contract_service_id',  // Foreign key on Collection (to connect with ContractService)
    //         'id',                   // Local key on User (to connect with Contract)
    //         'id'                    // Local key on Contract (to connect with Collection)
    //     ); // Apply department filter
    // }




}

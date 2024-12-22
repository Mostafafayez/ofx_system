<?php

namespace App\Rules;


use Illuminate\Contracts\Validation\Rule;
use Spatie\Permission\Models\Role;

class HasRole implements Rule
{
    private $role;

    public function __construct($role)
    {
        $this->role = $role;
    }

    public function passes($attribute, $value)
    {
        $user = \App\Models\User::find($value);
        return $user && $user->hasRole($this->role);
    }

    public function message()
    {
        return "The selected user must have the role of {$this->role}.";
    }
}

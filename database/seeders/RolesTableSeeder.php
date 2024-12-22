<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $roles = [
            ['name' => 'owner', 'guard_name' => 'sanctum', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'manager', 'guard_name' => 'sanctum', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'branch_manager', 'guard_name' => 'sanctum', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'teamleader', 'guard_name' => 'sanctum', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'sales_employee', 'guard_name' => 'sanctum', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'technical_employee', 'guard_name' => 'sanctum', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'accountant', 'guard_name' => 'sanctum', 'created_at' => now(), 'updated_at' => now()],
        ];

        // Insert roles into the database
        DB::table('roles')->insert($roles);
    }
}

<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (Role::ROLES as $role) {
            Role::query()->updateOrCreate(['id' => $role['id']],['name' => $role['name']]);
        }
        User::query()->whereNull('role_id')->update(['role_id' => 1]);
    }
}

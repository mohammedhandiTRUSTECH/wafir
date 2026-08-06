<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Admin::query()->updateOrCreate([
            'email' => 'admin@fayhaa.com',
        ],[
            'name' => 'Admin',
            'email' => 'admin@fayhaa.com',
            'password' => 123456,
            'is_active' => true,
        ]);
    }
}

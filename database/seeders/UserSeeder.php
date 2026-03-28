<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('name', 'admin')->first();
        $kasirRole = Role::where('name', 'kasir')->first();
        $kitchenRole = Role::where('name', 'kitchen')->first();

        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@cafe.com',
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id,
            'remember_token' => Str::random(10),
        ]);

        User::create([
            'name' => 'Kasir 1',
            'email' => 'kasir@cafe.com',
            'password' => Hash::make('password'),
            'role_id' => $kasirRole->id,
            'remember_token' => Str::random(10),
        ]);

        User::create([
            'name' => 'Koki Dapur',
            'email' => 'kitchen@cafe.com',
            'password' => Hash::make('password'),
            'role_id' => $kitchenRole->id,
            'remember_token' => Str::random(10),
        ]);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => "Antonio Puerta",
            'email' => "antoniolenovo115@gmail.com",
            'password' =>bcrypt('12345678'),
            'admin' => 1,
            'position_id' => 1,
        ]);
        User::create([
            'name' => "Rosa",
            'email' => "ochatineo@gmail.com",
            'password' =>bcrypt('12345678'),
            'admin' => 0,
            'position_id' => 3,
        ]);

    }
}

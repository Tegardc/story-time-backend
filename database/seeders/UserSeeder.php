<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();
        $data = [
            [
                'name' => 'tegar',
                'username' => 'tegardc',
                'email' => 'tegardc@gmail.com',
                'password' => '123123123',
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'name' => 'santi',
                'username' => 'santi',
                'email' => 'santi@gmail.com',
                'password' => '123123123',
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'name' => 'fredy',
                'username' => 'fredy',
                'email' => 'fredy@gmail.com',
                'password' => '123123123',
                'created_at' => $now,
                'updated_at' => $now
            ],
        ];
        foreach ($data as $user) {
            User::insert($user);
        }
        //
    }
}

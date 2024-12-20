<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();
        $data = [
            [
                'name' => 'Romance'
            ],
            [
                'name' => 'Action'
            ],
            [
                'name' => 'Horror'
            ],
            [
                'name' => 'Fantasy'
            ],
        ];
        foreach ($data as $category) {
            Category::insert($category);
        }
        //
    }
}

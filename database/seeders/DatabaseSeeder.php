<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Створюємо категорії
        \App\Models\Category::insert([
            ['name' => 'IT Issues'],
            ['name' => 'Finance'],
            ['name' => 'Administration']
        ]);

        // Створюємо пріоритети
        \App\Models\Priority::insert([
            ['name' => 'Low'],
            ['name' => 'Medium'],
            ['name' => 'High']
        ]);
    }
}

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
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
        if (User::where('email', 'admin@gmail.com')->count() == 0) {
            User::create([
                'name' => 'Admin Parking',
                'email' => 'admin@gmail.com',
                'password' => Hash::make('12345678'), // Sử dụng Facade Hash đã import ở trên
            ]);
        }
    }
}

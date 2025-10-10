<?php

namespace Database\Seeders;

use App\Models\Driver;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DriverSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Driver::create([
            'name' => 'Driver 1',
            'phone' => '1234567890',
            'active' => true,
        ]);

        Driver::create([
            'name' => 'Driver 2',
            'phone' => '1234567891',
            'active' => true,
        ]);

        Driver::create([
            'name' => 'Driver 3',
            'phone' => '1234567892',
            'active' => true,
        ]);

        Driver::create([
            'name' => 'Driver 4',
            'phone' => '1234567893',
            'active' => true,
        ]);

        Driver::create([
            'name' => 'Driver 5',
            'phone' => '1234567894',
            'active' => true,
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\Courier;
use Illuminate\Database\Seeder;

class CourierSeeder extends Seeder
{
    public function run(): void
    {
        Courier::factory()->count(50)->create();
    }
}

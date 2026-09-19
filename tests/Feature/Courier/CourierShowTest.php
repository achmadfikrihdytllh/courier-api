<?php

namespace Tests\Feature\Courier;

use App\Models\Courier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourierShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_all_courier_data(): void
    {
        $courier = Courier::factory()->create([
            'name' => 'Budiono Hadi Agung',
            'phone' => '081234567890',
            'level' => 4,
            'vehicle_type' => 'motorcycle',
        ]);

        $this->getJson(route('couriers.show', $courier))
            ->assertOk()
            ->assertJsonStructure(['data' => [
                'id', 'name', 'phone', 'email', 'id_card_number', 'address',
                'vehicle_type', 'vehicle_plate', 'level', 'is_active',
                'created_at', 'updated_at',
            ]])
            ->assertJsonPath('data.id', $courier->id)
            ->assertJsonPath('data.name', 'Budiono Hadi Agung')
            ->assertJsonPath('data.phone', '081234567890')
            ->assertJsonPath('data.level', 4)
            ->assertJsonPath('data.vehicle_type', 'motorcycle')
            ->assertJsonPath('data.is_active', true);
    }

    public function test_it_returns_404_for_unknown_courier(): void
    {
        $this->getJson(route('couriers.show', 999))->assertNotFound();
    }
}

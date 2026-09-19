<?php

namespace Tests\Feature\Courier;

use App\Models\Courier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourierDestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_the_courier_from_the_database(): void
    {
        $courier = Courier::factory()->create();
        $other = Courier::factory()->create();

        $this->deleteJson(route('couriers.destroy', $courier))->assertNoContent();

        $this->assertDatabaseMissing('couriers', ['id' => $courier->id]);
        $this->assertDatabaseHas('couriers', ['id' => $other->id]);
    }

    public function test_it_returns_404_for_unknown_courier(): void
    {
        $this->deleteJson(route('couriers.destroy', 999))->assertNotFound();
    }
}

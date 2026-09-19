<?php

namespace Tests\Feature\Courier;

use App\Models\Courier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CourierStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_courier_and_persists_it(): void
    {
        $payload = Courier::factory()->raw(['name' => 'Budiono Hadi Agung', 'level' => 3]);

        $this->postJson(route('couriers.store'), $payload)
            ->assertCreated()
            ->assertJsonPath('data.name', 'Budiono Hadi Agung')
            ->assertJsonPath('data.level', 3);

        $this->assertDatabaseCount('couriers', 1);
        $this->assertDatabaseHas('couriers', $payload);
    }

    public function test_optional_fields_can_be_omitted_and_defaults_are_applied(): void
    {
        $payload = [
            'name' => 'Sari Dewi',
            'phone' => '081298765432',
            'vehicle_type' => 'bicycle', // sepeda tidak wajib plat
            'level' => 1,
        ];

        $this->postJson(route('couriers.store'), $payload)
            ->assertCreated()
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.vehicle_plate', null);

        $this->assertDatabaseHas('couriers', [...$payload, 'is_active' => true]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    #[DataProvider('invalidPayloads')]
    public function test_it_rejects_invalid_input(array $overrides, string $field): void
    {
        $payload = array_merge(Courier::factory()->raw(), $overrides);

        $this->postJson(route('couriers.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);

        $this->assertDatabaseCount('couriers', 0);
    }

    public static function invalidPayloads(): array
    {
        return [
            'missing name' => [['name' => null], 'name'],
            'name too short' => [['name' => 'A'], 'name'],
            'name too long' => [['name' => str_repeat('a', 101)], 'name'],
            'missing phone' => [['phone' => null], 'phone'],
            'invalid phone format' => [['phone' => '12345'], 'phone'],
            'invalid email' => [['email' => 'not-an-email'], 'email'],
            'nik not 16 digits' => [['id_card_number' => '12345'], 'id_card_number'],
            'nik not numeric' => [['id_card_number' => 'abcdefghijklmnop'], 'id_card_number'],
            'address too long' => [['address' => str_repeat('a', 501)], 'address'],
            'missing vehicle type' => [['vehicle_type' => null], 'vehicle_type'],
            'unknown vehicle type' => [['vehicle_type' => 'rocket'], 'vehicle_type'],
            'motorcycle without plate' => [['vehicle_type' => 'motorcycle', 'vehicle_plate' => null], 'vehicle_plate'],
            'plate too long' => [['vehicle_plate' => str_repeat('A', 16)], 'vehicle_plate'],
            'missing level' => [['level' => null], 'level'],
            'level below min' => [['level' => 0], 'level'],
            'level above max' => [['level' => 6], 'level'],
            'level not integer' => [['level' => 'abc'], 'level'],
            'level decimal' => [['level' => 2.5], 'level'],
            'is_active not boolean' => [['is_active' => 'maybe'], 'is_active'],
        ];
    }

    public function test_phone_email_and_id_card_number_must_be_unique(): void
    {
        $existing = Courier::factory()->create();

        foreach (['phone', 'email', 'id_card_number'] as $field) {
            $payload = Courier::factory()->raw([$field => $existing->{$field}]);

            $this->postJson(route('couriers.store'), $payload)
                ->assertUnprocessable()
                ->assertJsonValidationErrors($field);
        }

        $this->assertDatabaseCount('couriers', 1);
    }
}

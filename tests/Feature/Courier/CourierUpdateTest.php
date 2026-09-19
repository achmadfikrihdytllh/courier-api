<?php

namespace Tests\Feature\Courier;

use App\Models\Courier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CourierUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_put_replaces_courier_data_and_persists_it(): void
    {
        $courier = Courier::factory()->create(['name' => 'Old Name', 'level' => 1]);
        $payload = Courier::factory()->raw(['name' => 'New Name', 'level' => 4]);

        $this->putJson(route('couriers.update', $courier), $payload)
            ->assertOk()
            ->assertJsonPath('data.id', $courier->id)
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.level', 4);

        $this->assertDatabaseHas('couriers', ['id' => $courier->id, ...$payload]);
        $this->assertDatabaseMissing('couriers', ['name' => 'Old Name']);
        $this->assertDatabaseCount('couriers', 1);
    }

    public function test_put_requires_all_required_fields(): void
    {
        $courier = Courier::factory()->create();

        $this->putJson(route('couriers.update', $courier), ['name' => 'Only Name'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone', 'vehicle_type', 'level']);
    }

    public function test_patch_updates_only_given_fields(): void
    {
        $courier = Courier::factory()->create(['name' => 'Budi', 'level' => 1]);

        $this->patchJson(route('couriers.update', $courier), ['level' => 3])
            ->assertOk()
            ->assertJsonPath('data.level', 3);

        $this->assertDatabaseHas('couriers', [
            'id' => $courier->id,
            'name' => 'Budi',
            'phone' => $courier->phone,
            'level' => 3,
        ]);
    }

    public function test_courier_can_keep_its_own_unique_values(): void
    {
        $courier = Courier::factory()->create();

        $this->putJson(route('couriers.update', $courier), Courier::factory()->raw([
            'phone' => $courier->phone,
            'email' => $courier->email,
            'id_card_number' => $courier->id_card_number,
        ]))->assertOk();
    }

    public function test_it_cannot_take_unique_values_of_another_courier(): void
    {
        $other = Courier::factory()->create();
        $courier = Courier::factory()->create();

        foreach (['phone', 'email', 'id_card_number'] as $field) {
            $this->patchJson(route('couriers.update', $courier), [$field => $other->{$field}])
                ->assertUnprocessable()
                ->assertJsonValidationErrors($field);
        }
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    #[DataProvider('invalidChanges')]
    public function test_it_rejects_invalid_input_and_leaves_database_untouched(array $changes, string $field): void
    {
        $courier = Courier::factory()->create();

        $this->patchJson(route('couriers.update', $courier), $changes)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);

        $this->assertDatabaseHas('couriers', [
            'id' => $courier->id,
            'name' => $courier->name,
            'phone' => $courier->phone,
            'level' => $courier->level,
        ]);
    }

    public static function invalidChanges(): array
    {
        return [
            'empty name' => [['name' => null], 'name'],
            'name too short' => [['name' => 'A'], 'name'],
            'invalid phone' => [['phone' => 'abc'], 'phone'],
            'invalid email' => [['email' => 'nope'], 'email'],
            'invalid nik' => [['id_card_number' => '123'], 'id_card_number'],
            'unknown vehicle type' => [['vehicle_type' => 'rocket'], 'vehicle_type'],
            'level below min' => [['level' => 0], 'level'],
            'level above max' => [['level' => 6], 'level'],
            'is_active not boolean' => [['is_active' => 'maybe'], 'is_active'],
        ];
    }

    public function test_it_returns_404_for_unknown_courier(): void
    {
        $this->putJson(route('couriers.update', 999), Courier::factory()->raw())->assertNotFound();
    }
}

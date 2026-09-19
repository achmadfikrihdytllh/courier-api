<?php

namespace Tests\Feature\Courier;

use App\Models\Courier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CourierIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_paginated_couriers(): void
    {
        Courier::factory()->count(20)->create();

        $this->getJson(route('couriers.index'))
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.total', 20)
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.last_page', 2);

        $this->getJson(route('couriers.index', ['per_page' => 5, 'page' => 2]))
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.current_page', 2);
    }

    public function test_it_sorts_by_name_by_default(): void
    {
        Courier::factory()->create(['name' => 'Charlie']);
        Courier::factory()->create(['name' => 'Alpha']);
        Courier::factory()->create(['name' => 'Bravo']);

        $response = $this->getJson(route('couriers.index'))->assertOk();

        $this->assertSame(['Alpha', 'Bravo', 'Charlie'], $this->names($response));
    }

    public function test_it_can_sort_by_registration_date(): void
    {
        // Urutan nama (Amy, Bayu, Zed) sengaja berbeda dengan urutan tanggal daftar.
        Courier::factory()->create(['name' => 'Bayu', 'created_at' => now()->subDays(3)]);
        Courier::factory()->create(['name' => 'Zed', 'created_at' => now()->subDays(2)]);
        Courier::factory()->create(['name' => 'Amy', 'created_at' => now()->subDay()]);

        $oldestFirst = $this->getJson(route('couriers.index', ['sort' => 'created_at']))->assertOk();
        $newestFirst = $this->getJson(route('couriers.index', ['sort' => 'created_at', 'direction' => 'desc']))->assertOk();
        $nameDesc = $this->getJson(route('couriers.index', ['direction' => 'desc']))->assertOk();

        $this->assertSame(['Bayu', 'Zed', 'Amy'], $this->names($oldestFirst));
        $this->assertSame(['Amy', 'Zed', 'Bayu'], $this->names($newestFirst));
        $this->assertSame(['Zed', 'Bayu', 'Amy'], $this->names($nameDesc));
    }

    public function test_it_searches_by_every_word_in_any_order(): void
    {
        Courier::factory()->create(['name' => 'Budiono Hadi Agung']);
        Courier::factory()->create(['name' => 'Agung Santoso']);
        Courier::factory()->create(['name' => 'Siti Budiman']);

        foreach (['budi agung', 'AGUNG budi', 'hadi'] as $keyword) {
            $response = $this->getJson(route('couriers.index', ['search' => $keyword]))->assertOk();

            $this->assertSame(['Budiono Hadi Agung'], $this->names($response), "keyword: {$keyword}");
        }

        $this->getJson(route('couriers.index', ['search' => 'tidak ada']))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_search_treats_like_wildcards_as_plain_characters(): void
    {
        Courier::factory()->create(['name' => 'Budi']);
        Courier::factory()->create(['name' => 'Rina 100% Fast']);

        $percent = $this->getJson(route('couriers.index', ['search' => '%']))->assertOk();
        $underscore = $this->getJson(route('couriers.index', ['search' => '_']))->assertOk();

        $this->assertSame(['Rina 100% Fast'], $this->names($percent));
        $this->assertSame([], $this->names($underscore));
    }

    public function test_it_filters_by_multiple_levels(): void
    {
        foreach (range(1, 5) as $level) {
            Courier::factory()->create(['level' => $level]);
        }

        $response = $this->getJson(route('couriers.index', ['level' => '2,3']))
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $levels = collect($response->json('data'))->pluck('level')->sort()->values()->all();
        $this->assertSame([2, 3], $levels);

        $this->getJson(route('couriers.index', ['level' => '4']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.level', 4);
    }

    public function test_search_level_and_sort_can_be_combined(): void
    {
        Courier::factory()->create(['name' => 'Budi Satu', 'level' => 2, 'created_at' => now()->subDays(2)]);
        Courier::factory()->create(['name' => 'Budi Dua', 'level' => 3, 'created_at' => now()->subDay()]);
        Courier::factory()->create(['name' => 'Budi Tiga', 'level' => 5]);
        Courier::factory()->create(['name' => 'Andi Empat', 'level' => 2]);

        $response = $this->getJson(route('couriers.index', [
            'search' => 'budi',
            'level' => '2,3',
            'sort' => 'created_at',
            'direction' => 'desc',
        ]))->assertOk();

        $this->assertSame(['Budi Dua', 'Budi Satu'], $this->names($response));
    }

    /**
     * @param  array<string, mixed>  $query
     */
    #[DataProvider('invalidQueries')]
    public function test_it_rejects_invalid_query_parameters(array $query, string $field): void
    {
        $this->getJson(route('couriers.index', $query))
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    }

    public static function invalidQueries(): array
    {
        return [
            'level above max' => [['level' => '6'], 'level'],
            'level below min' => [['level' => '0'], 'level'],
            'level with empty item' => [['level' => '2,,3'], 'level'],
            'level not numeric' => [['level' => 'abc'], 'level'],
            'unknown sort column' => [['sort' => 'phone'], 'sort'],
            'unknown direction' => [['direction' => 'sideways'], 'direction'],
            'per_page too big' => [['per_page' => 101], 'per_page'],
            'per_page zero' => [['per_page' => 0], 'per_page'],
            'search too long' => [['search' => str_repeat('a', 101)], 'search'],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function names(TestResponse $response): array
    {
        return collect($response->json('data'))->pluck('name')->all();
    }
}

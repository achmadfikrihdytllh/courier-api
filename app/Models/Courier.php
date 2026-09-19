<?php

namespace App\Models;

use App\Enums\VehicleType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Courier extends Model
{
    use HasFactory;

    public const MIN_LEVEL = 1;
    public const MAX_LEVEL = 5;

    /** Kolom yang boleh dipakai frontend untuk sorting. */
    public const SORTABLE_COLUMNS = ['name', 'created_at'];

    protected $fillable = [
        'name',
        'phone',
        'email',
        'id_card_number',
        'address',
        'vehicle_type',
        'vehicle_plate',
        'level',
        'is_active',
    ];

    protected $attributes = [
        'level' => self::MIN_LEVEL,
        'is_active' => true,
    ];

    protected $casts = [
        'vehicle_type' => VehicleType::class,
        'level' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Cari berdasarkan nama. Setiap kata harus ada di nama (urutan bebas),
     * sehingga "budi agung" cocok dengan "Budiono Hadi Agung".
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $words = preg_split('/\s+/', trim((string) $term), -1, PREG_SPLIT_NO_EMPTY);

        foreach ($words as $word) {
            // "!" sebagai escape char agar karakter % dan _ dari user tidak jadi wildcard,
            // dan portable di MySQL/SQLite/PostgreSQL.
            $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $word);
            $query->whereRaw("name LIKE ? ESCAPE '!'", ['%'.$escaped.'%']);
        }

        return $query;
    }

    /**
     * @param  array<int, int>  $levels  kosong = tidak difilter
     */
    public function scopeOfLevels(Builder $query, array $levels): Builder
    {
        return $levels === [] ? $query : $query->whereIn('level', $levels);
    }

    /**
     * Sorting yang aman (whitelist) + tie-breaker id supaya pagination stabil.
     */
    public function scopeSorted(Builder $query, string $column = 'name', string $direction = 'asc'): Builder
    {
        $column = in_array($column, self::SORTABLE_COLUMNS, true) ? $column : 'name';
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';

        return $query->orderBy($column, $direction)->orderBy('id', $direction);
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('couriers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->index();
            $table->string('phone', 20)->unique();
            $table->string('email')->nullable()->unique();
            $table->string('id_card_number', 16)->nullable()->unique(); // NIK
            $table->text('address')->nullable();
            $table->string('vehicle_type', 20);
            $table->string('vehicle_plate', 15)->nullable();
            $table->unsignedTinyInteger('level')->default(1)->index(); // 1-5
            $table->boolean('is_active')->default(true);
            $table->timestamps(); // created_at = tanggal didaftarkan
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('couriers');
    }
};

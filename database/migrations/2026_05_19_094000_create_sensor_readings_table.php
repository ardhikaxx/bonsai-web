<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sensor_readings', function (Blueprint $table) {
            $table->id();
            $table->timestamp('sensor_timestamp')->nullable()->index();
            $table->decimal('temperature_c', 8, 3);
            $table->decimal('humidity_air_pct', 8, 3);
            $table->decimal('soil_moisture_pct', 8, 3);
            $table->string('firebase_waktu')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensor_readings');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prediction_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sensor_reading_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('prediction_timestamp')->nullable()->index();
            $table->decimal('predicted_soil_moisture_pct', 8, 3)->nullable();
            $table->decimal('prediction_probability', 8, 5)->nullable();
            $table->string('prediction_class')->nullable();
            $table->string('pump_status')->nullable();
            $table->string('irrigation_reason')->nullable();
            $table->json('raw_output')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prediction_logs');
    }
};

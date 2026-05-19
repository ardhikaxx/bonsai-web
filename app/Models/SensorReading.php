<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SensorReading extends Model
{
    protected $fillable = [
        'sensor_timestamp',
        'temperature_c',
        'humidity_air_pct',
        'soil_moisture_pct',
        'firebase_waktu',
        'raw_payload',
    ];

    protected $casts = [
        'sensor_timestamp' => 'datetime',
        'temperature_c' => 'float',
        'humidity_air_pct' => 'float',
        'soil_moisture_pct' => 'float',
        'raw_payload' => 'array',
    ];
}

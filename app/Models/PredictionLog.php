<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PredictionLog extends Model
{
    protected $fillable = [
        'sensor_reading_id',
        'prediction_timestamp',
        'predicted_soil_moisture_pct',
        'prediction_probability',
        'prediction_class',
        'pump_status',
        'irrigation_reason',
        'raw_output',
    ];

    protected $casts = [
        'prediction_timestamp' => 'datetime',
        'predicted_soil_moisture_pct' => 'float',
        'prediction_probability' => 'float',
        'raw_output' => 'array',
    ];
}

<?php

namespace App\Http\Controllers;

use App\Models\PredictionLog;
use App\Models\SensorReading;

class DashboardController extends Controller
{
    public function index()
    {
        $latest = SensorReading::query()
            ->latest('sensor_timestamp')
            ->latest('id')
            ->first();

        $recentReadings = SensorReading::query()
            ->latest('sensor_timestamp')
            ->latest('id')
            ->limit(6)
            ->get()
            ->reverse()
            ->values();

        $latestPrediction = PredictionLog::query()
            ->latest('prediction_timestamp')
            ->latest('id')
            ->first();

        $data = [
            'kelembapan' => $latest?->soil_moisture_pct ?? 0,
            'kelembapan_udara' => $latest?->humidity_air_pct ?? 0,
            'suhu' => $latest?->temperature_c ?? 0,
            'hujan' => false,
            'pompa' => strtolower((string) ($latestPrediction?->pump_status ?? 'off')) === 'on',
            'riwayat' => $this->formatRiwayatData($recentReadings),
            'prediksi' => $this->formatPrediksiData(),
            'grafik' => $this->formatGrafikData($recentReadings),
        ];

        return view('dashboard', $data);
    }

    private function formatRiwayatData($readings): array
    {
        return $readings->map(function (SensorReading $reading) {
            return [
                'tanggal' => optional($reading->sensor_timestamp)->format('Y-m-d H:i:s') ?? $reading->created_at->format('Y-m-d H:i:s'),
                'kelembapan' => round($reading->soil_moisture_pct, 2),
                'kelembapan_udara' => round($reading->humidity_air_pct, 2),
                'suhu' => round($reading->temperature_c, 2),
                'hujan' => false,
            ];
        })->all();
    }

    private function formatGrafikData($readings): array
    {
        return [
            'labels' => $readings->map(fn (SensorReading $reading) => optional($reading->sensor_timestamp)->format('H:i') ?? $reading->created_at->format('H:i'))->all(),
            'kelembapan' => $readings->map(fn (SensorReading $reading) => round($reading->soil_moisture_pct, 2))->all(),
            'kelembapan_udara' => $readings->map(fn (SensorReading $reading) => round($reading->humidity_air_pct, 2))->all(),
            'suhu' => $readings->map(fn (SensorReading $reading) => round($reading->temperature_c, 2))->all(),
            'hujan' => $readings->map(fn () => 0)->all(),
        ];
    }

    private function formatPrediksiData(): array
    {
        return PredictionLog::query()
            ->latest('prediction_timestamp')
            ->latest('id')
            ->limit(6)
            ->get()
            ->reverse()
            ->values()
            ->map(function (PredictionLog $prediction) {
                $pumpOn = strtolower((string) $prediction->pump_status) === 'on';

                return [
                    'tanggal' => optional($prediction->prediction_timestamp)->format('Y-m-d H:i:s') ?? $prediction->created_at->format('Y-m-d H:i:s'),
                    'kelembapan_prediksi' => round((float) $prediction->predicted_soil_moisture_pct, 2),
                    'status' => $pumpOn ? 'Siram' : 'Tidak Siram',
                    'pompa_status' => $pumpOn,
                ];
            })
            ->all();
    }
}

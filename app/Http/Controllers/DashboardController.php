<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PredictionLog;
use App\Models\SensorReading;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Ambil status sistem dari Firebase (Manual fetch untuk kartu status)
        $systemActive = false;
        try {
            $firebaseData = json_decode(file_get_contents(config('services.firebase.database_url') . '/Pompa.json'), true);
            $systemActive = isset($firebaseData['system_active']) && strtolower((string)$firebaseData['system_active']) === 'on';
        } catch (\Exception $e) {
            // Fallback jika gagal
        }

        // Untuk kartu status (selalu ambil yang terbaru secara absolut)
        $latest = SensorReading::query()
            ->latest('sensor_timestamp')
            ->latest('id')
            ->first();

        $latestPrediction = PredictionLog::query()
            ->latest('prediction_timestamp')
            ->latest('id')
            ->first();

        // Untuk riwayat dan grafik (terpengaruh filter)
        $query = SensorReading::query();
        if ($startDate) {
            $query->whereDate('sensor_timestamp', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('sensor_timestamp', '<=', $endDate);
        }

        // Ambil data untuk grafik (10 data terbaru untuk divisualisasikan)
        $graphReadings = (clone $query)->latest('sensor_timestamp')
            ->latest('id')
            ->limit(10)
            ->get()
            ->reverse()
            ->values();

        // Ambil data untuk tabel dengan pagination (10 data per halaman, terbaru di atas)
        $paginatedReadings = SensorReading::query()
            ->when($startDate, function ($q) use ($startDate) {
                return $q->whereDate('sensor_timestamp', '>=', $startDate);
            })
            ->when($endDate, function ($q) use ($endDate) {
                return $q->whereDate('sensor_timestamp', '<=', $endDate);
            })
            ->latest('sensor_timestamp')
            ->latest('id')
            ->paginate(10)
            ->appends($request->query());

        $data = [
            'kelembapan' => $latest?->soil_moisture_pct ?? 0,
            'kelembapan_udara' => $latest?->humidity_air_pct ?? 0,
            'suhu' => $latest?->temperature_c ?? 0,
            'hujan' => false,
            'pompa' => strtolower((string) ($latestPrediction?->pump_status ?? 'off')) === 'on',
            'sistem' => $systemActive,
            'riwayat' => $paginatedReadings, // Kirim objek paginator langsung
            'prediksi' => $this->formatPrediksiData(collect($paginatedReadings->items())->pluck('id')),
            'grafik' => $this->formatGrafikData($graphReadings),
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]
        ];

        return view('dashboard', $data);
    }

    public function export(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = SensorReading::query();
        if ($startDate) {
            $query->whereDate('sensor_timestamp', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('sensor_timestamp', '<=', $endDate);
        }

        $readings = $query->orderBy('sensor_timestamp', 'asc')->get();

        $response = new StreamedResponse(function () use ($readings) {
            $handle = fopen('php://output', 'w');
            
            // Header Excel/CSV
            fputcsv($handle, [
                'ID', 
                'Waktu Sensor', 
                'Kelembapan Tanah (%)', 
                'Kelembapan Udara (%)', 
                'Suhu (°C)', 
                'Prediksi Kelembapan (%)', 
                'Status Prediksi',
                'Status Pompa'
            ]);

            foreach ($readings as $reading) {
                $prediction = PredictionLog::where('sensor_reading_id', $reading->id)->first();
                
                fputcsv($handle, [
                    $reading->id,
                    $reading->sensor_timestamp?->format('Y-m-d H:i:s') ?? $reading->created_at->format('Y-m-d H:i:s'),
                    round($reading->soil_moisture_pct, 2),
                    round($reading->humidity_air_pct, 2),
                    round($reading->temperature_c, 2),
                    $prediction ? round((float) $prediction->predicted_soil_moisture_pct, 2) : '-',
                    $prediction ? $prediction->prediction_class : '-',
                    $prediction ? $prediction->pump_status : '-',
                ]);
            }

            fclose($handle);
        });

        $filename = 'laporan_sensor_' . now()->format('Ymd_His') . '.csv';
        
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }

    private function formatRiwayatData($readings): array
    {
        return $readings->reverse()->values()->map(function (SensorReading $reading) {
            return [
                'id' => $reading->id,
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

    private function formatPrediksiData($readingIds = null): array
    {
        $query = PredictionLog::query();
        
        if ($readingIds) {
            $query->whereIn('sensor_reading_id', $readingIds);
        } else {
            $query->latest('prediction_timestamp')->latest('id')->limit(6);
        }

        return $query->get()
            ->keyBy('sensor_reading_id')
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

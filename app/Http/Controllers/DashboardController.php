<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Data dummy untuk contoh
        $data = [
            'kelembapan' => 65,
            'kelembapan_udara' => 55,
            'suhu' => 25.5,
            'hujan' => false,
            'riwayat' => $this->getRiwayatData(),
            'grafik' => $this->getGrafikData()
        ];

        return view('dashboard', $data);
    }

    private function getRiwayatData()
    {
        return [
            [
                'tanggal' => '2023-05-01 08:00', 
                'kelembapan' => 62, 
                'kelembapan_udara' => 58,
                'suhu' => 26.5, 
                'hujan' => false
            ],
            [
                'tanggal' => '2023-05-01 12:00', 
                'kelembapan' => 65, 
                'kelembapan_udara' => 52,
                'suhu' => 28.0, 
                'hujan' => false
            ],
            [
                'tanggal' => '2023-05-01 16:00', 
                'kelembapan' => 68, 
                'kelembapan_udara' => 62,
                'suhu' => 27.0, 
                'hujan' => true
            ],
            [
                'tanggal' => '2023-05-02 08:00', 
                'kelembapan' => 63, 
                'kelembapan_udara' => 60,
                'suhu' => 26.0, 
                'hujan' => false
            ],
            [
                'tanggal' => '2023-05-02 12:00', 
                'kelembapan' => 67, 
                'kelembapan_udara' => 55,
                'suhu' => 29.0, 
                'hujan' => false
            ],
            [
                'tanggal' => '2023-05-02 16:00', 
                'kelembapan' => 70, 
                'kelembapan_udara' => 65,
                'suhu' => 27.5, 
                'hujan' => true
            ],
        ];
    }

    private function getGrafikData()
    {
        return [
            'labels' => ['00:00', '04:00', '08:00', '12:00', '16:00', '20:00'],
            'kelembapan' => [60, 62, 65, 68, 70, 67],          // Data kelembapan tanah
            'kelembapan_udara' => [55, 58, 52, 62, 65, 60],    // Data kelembapan udara baru
            'suhu' => [25.0, 24.5, 26.0, 28.5, 27.0, 26.0],
            'hujan' => [0, 0, 0, 1, 1, 0]
        ];
    }
}
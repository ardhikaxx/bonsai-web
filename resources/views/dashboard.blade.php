@extends('layouts.app')

@section('content')
    <div class="space-y-8">
        <!-- Header -->
        <div class="relative bg-gradient-to-r from-green-600 to-green-800 rounded-2xl shadow-2xl overflow-hidden">
            <div class="absolute inset-0 bg-black opacity-20"></div>
            <div class="relative z-10 p-8 flex flex-col md:flex-row items-center justify-between">
                <div class="text-white">
                    <h1 class="text-3xl md:text-4xl font-bold mb-2">Monitor Kebun Bonsai</h1>
                    <p class="text-emerald-100 text-lg">Pemantauan real-time untuk koleksi bonsai Anda</p>
                </div>
                <div class="mt-4 md:mt-0">
                    <div class="flex items-center space-x-3 bg-gradient-to-r from-indigo-500/20 to-purple-500/20 backdrop-blur-sm rounded-xl px-5 py-3 shadow-lg cursor-pointer"
                        onclick="togglePompa()">
                        <div class="flex items-center space-x-3">
                            <div id="pompaIndicator"
                                class="w-3 h-3 rounded-full {{ $pompa ? 'bg-green-400 animate-pulse' : 'bg-gray-400' }}">
                            </div>
                            <span class="text-white font-semibold">Pompa</span>
                            <span id="pompaStatus"
                                class="px-3 py-1 rounded-full text-sm font-bold {{ $pompa ? 'bg-green-500/20 text-green-200' : 'bg-gray-500/20 text-gray-200' }}">
                                {{ $pompa ? 'ON' : 'OFF' }}
                            </span>
                        </div>
                    </div>
                </div>

                <script>
                    let pompaState = {{ $pompa ? 'true' : 'false' }};

                    function togglePompa() {
                        pompaState = !pompaState;

                        const indicator = document.getElementById('pompaIndicator');
                        const status = document.getElementById('pompaStatus');

                        if (pompaState) {
                            indicator.classList.remove('bg-gray-400');
                            indicator.classList.add('bg-green-400', 'animate-pulse');
                            status.classList.remove('bg-gray-500/20', 'text-gray-200');
                            status.classList.add('bg-green-500/20', 'text-green-200');
                            status.textContent = 'ON';
                        } else {
                            indicator.classList.remove('bg-green-400', 'animate-pulse');
                            indicator.classList.add('bg-gray-400');
                            status.classList.remove('bg-green-500/20', 'text-green-200');
                            status.classList.add('bg-gray-500/20', 'text-gray-200');
                            status.textContent = 'OFF';
                        }
                    }
                </script>
            </div>
        </div>

        <!-- Kartu Sensor dengan Glass Morphism -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Kartu Kelembapan Tanah -->
            <div
                class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl overflow-hidden border border-white/20 transition-all hover:shadow-2xl hover:-translate-y-1">
                <div class="p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-700">Kelembapan Tanah</h2>
                            <p class="text-4xl font-bold text-blue-600 mt-2">{{ $kelembapan }}%</p>
                            <div class="mt-1">
                                @if ($kelembapan < 40)
                                    <span class="text-xs px-2 py-1 rounded-full bg-red-100 text-red-800">Terlalu
                                        Kering</span>
                                @elseif($kelembapan > 80)
                                    <span class="text-xs px-2 py-1 rounded-full bg-blue-100 text-blue-800">Terlalu
                                        Basah</span>
                                @else
                                    <span class="text-xs px-2 py-1 rounded-full bg-green-100 text-green-800">Ideal</span>
                                @endif
                            </div>
                        </div>
                        <div
                            class="p-4 rounded-full bg-gradient-to-br from-blue-100 to-blue-200 text-blue-600 shadow-inner">
                            <i class="fas fa-seedling text-3xl"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="flex justify-between text-sm text-gray-500 mb-1">
                            <span>Kering</span>
                            <span>Basah</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                            <div class="bg-gradient-to-r from-blue-400 to-blue-600 h-full rounded-full transition-all duration-500"
                                style="width: {{ $kelembapan }}%"></div>
                        </div>
                    </div>
                </div>

                <!-- Pembatas -->
                <div class="border-t border-gray-200 mx-4"></div>

                <!-- Kartu Kelembapan Udara -->
                <div class="p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-700">Kelembapan Udara</h2>
                            <p class="text-4xl font-bold text-green-600 mt-2">{{ $kelembapan_udara }}%</p>
                            <div class="mt-1">
                                <span class="text-xs px-2 py-1 rounded-full bg-green-100 text-green-800">Normal</span>
                            </div>
                        </div>
                        <div
                            class="p-4 rounded-full bg-gradient-to-br from-green-100 to-green-200 text-green-600 shadow-inner">
                            <i class="fas fa-wind text-3xl"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="flex justify-between text-sm text-gray-500 mb-1">
                            <span>Rendah</span>
                            <span>Tinggi</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                            <div class="bg-gradient-to-r from-green-400 to-green-600 h-full rounded-full transition-all duration-500"
                                style="width: {{ $kelembapan_udara }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kartu Suhu -->
            <!-- Kartu Suhu - Desain Lebih Optimal -->
            <div
                class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl overflow-hidden border border-white/20 transition-all hover:shadow-2xl hover:-translate-y-1 h-full flex flex-col">
                <div class="p-6 flex-1 flex flex-col">
                    <!-- Header dengan Icon Besar -->
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                                <i class="fas fa-temperature-high text-orange-500"></i>
                                <span>Suhu Lingkungan</span>
                            </h2>
                            <p class="text-sm text-gray-500 mt-1">Update terakhir: {{ now()->format('H:i') }}</p>
                        </div>
                        <div
                            class="px-6 py-4 rounded-full bg-gradient-to-br from-blue-100 to-blue-200 text-blue-600 shadow-inner">
                            <i class="fas fa-thermometer-three-quarters text-3xl text-orange-600"></i>
                        </div>
                    </div>

                    <!-- Nilai Suhu Utama -->
                    <div class="flex-1 flex flex-col justify-center items-center my-4">
                        <div class="relative">
                            <p class="text-6xl font-bold text-orange-600 text-center">{{ $suhu }}°C</p>
                            <!-- Indikator Status -->
                            <div class="mt-3 flex justify-center">
                                @if ($suhu < 20)
                                    <span
                                        class="text-xs px-3 py-1.5 rounded-full bg-blue-100 text-blue-800 font-medium d-flex justify-content-center align-items-center gap-1">
                                        <i class="fas fa-snowflake"></i> Terlalu Dingin
                                    </span>
                                @elseif($suhu > 30)
                                    <span
                                        class="text-xs px-3 py-1.5 rounded-full bg-red-100 text-red-800 font-medium d-flex justify-content-center align-items-center gap-1">
                                        <i class="fas fa-fire"></i> Terlalu Panas
                                    </span>
                                @else
                                    <span
                                        class="text-xs px-3 py-1.5 rounded-full bg-green-100 text-green-800 font-medium d-flex justify-content-center align-items-center gap-1">
                                        <i class="fas fa-check-circle"></i> Suhu Ideal
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Progress Bar dengan Range -->
                    <div class="mt-auto">
                        <div class="flex justify-between text-sm text-gray-500 mb-2">
                            <span class="flex items-center"><i class="fas fa-temperature-low mr-1"></i> 10°C</span>
                            <span class="text-xs text-gray-400">Skala Suhu</span>
                            <span class="flex items-center"><i class="fas fa-temperature-high mr-1"></i> 40°C</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden shadow-inner">
                            <div class="bg-gradient-to-r from-blue-400 via-orange-400 to-red-600 h-full rounded-full transition-all duration-500"
                                style="width: {{ (($suhu - 10) / 30) * 100 }}%"></div>
                        </div>

                        <!-- Rekomendasi Suhu -->
                        <div class="mt-4 p-3 bg-gray-50 rounded-lg border border-gray-100">
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-info-circle text-orange-500 mr-2"></i>
                                <span>
                                    @if ($suhu < 20)
                                        Rekomendasi: Tingkatkan suhu ruangan
                                    @elseif($suhu > 30)
                                        Rekomendasi: Turunkan suhu ruangan
                                    @else
                                        Suhu dalam kondisi optimal
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kartu Hujan -->
            <div
                class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl overflow-hidden border border-white/20 transition-all hover:shadow-2xl hover:-translate-y-1">
                <div class="p-7 d-flex justify-content-center align-items-center flex-column mt-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-700">Sensor Hujan</h2>
                            <p class="text-4xl font-bold mt-2 {{ $hujan ? 'text-red-600' : 'text-green-600' }}">
                                {{ $hujan ? 'Hujan' : 'Cerah' }}
                            </p>
                            <p class="text-sm mt-1 {{ $hujan ? 'text-red-500' : 'text-green-500' }}">
                                <i class="fas {{ $hujan ? 'fa-umbrella' : 'fa-sun' }} mr-1"></i>
                                Penutup: {{ $hujan ? 'Tertutup' : 'Terbuka' }}
                            </p>
                        </div>
                        <div
                            class="p-4 rounded-full bg-gradient-to-br {{ $hujan ? 'from-red-100 to-red-200 text-red-600' : 'from-green-100 to-green-200 text-green-600' }} shadow-inner">
                            <i class="fas fa-cloud-{{ $hujan ? 'rain' : 'sun' }} text-3xl"></i>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-center">
                        <div
                            class="relative w-32 h-32 bg-gradient-to-br {{ $hujan ? 'from-gray-200 to-gray-300' : 'from-yellow-100 to-yellow-200' }} rounded-full flex items-center justify-center overflow-hidden shadow-inner">
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="w-24 h-24 rounded-full bg-white/80 backdrop-blur-sm"></div>
                            </div>
                            @if ($hujan)
                                <div class="absolute inset-0 animate-rain">
                                    @for ($i = 0; $i < 30; $i++)
                                        <div class="absolute w-1 h-3 bg-blue-400 rounded-full"
                                            style="top: -5px; left: {{ rand(0, 100) }}%; 
                                                animation: rain {{ rand(5, 15) / 10 }}s linear infinite;
                                                animation-delay: {{ rand(0, 20) / 10 }}s;">
                                        </div>
                                    @endfor
                                </div>
                                <div class="absolute top-8 left-1/2 transform -translate-x-1/2">
                                    <i class="fas fa-cloud text-6xl text-gray-500"></i>
                                </div>
                            @else
                                <div class="absolute inset-0 flex z-3 items-center justify-center">
                                    <i class="fas fa-sun text-5xl text-yellow-400 animate-pulse"></i>
                                </div>
                                <div class="absolute top-1 right-4">
                                    <i class="fas fa-cloud text-4xl text-gray-100"></i>
                                </div>
                                <div class="absolute bottom-3 left-3">
                                    <i class="fas fa-cloud text-4xl text-gray-100"></i>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-4 gap-6 items-start">
            <!-- Bagian Grafik -->
            <div class="xl:col-span-3 bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl overflow-hidden border border-white/20">
                <div class="p-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-700">Tren Lingkungan</h2>
                        <div class="flex space-x-2">
                            <button
                                class="px-3 py-1 bg-gray-100 rounded-lg text-sm text-gray-600 hover:bg-gray-200">24j</button>
                            <button
                                class="px-3 py-1 bg-emerald-600 text-white rounded-lg text-sm hover:bg-emerald-700">7h</button>
                            <button
                                class="px-3 py-1 bg-gray-100 rounded-lg text-sm text-gray-600 hover:bg-gray-200">30h</button>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <div>
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between mb-3">
                                <h3 class="text-sm font-semibold text-gray-500 flex items-center">
                                    <span class="w-3 h-3 bg-blue-500 rounded-full mr-2"></span>
                                    Kelembapan Tanah (%)
                                </h3>
                                <div class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full w-fit">Kisaran Optimal:
                                    40-80%</div>
                            </div>
                            <div class="bg-white rounded-xl p-4 shadow-inner border border-gray-100 h-64">
                                <canvas id="kelembapanChart"></canvas>
                            </div>
                        </div>
                        <div>
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between mb-3">
                                <h3 class="text-sm font-semibold text-gray-500 flex items-center">
                                    <span class="w-3 h-3 bg-green-500 rounded-full mr-2"></span>
                                    Kelembapan Udara (%)
                                </h3>
                                <div class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full w-fit">Kisaran Normal:
                                    50-80%</div>
                            </div>
                            <div class="bg-white rounded-xl p-4 shadow-inner border border-gray-100 h-64">
                                <canvas id="kelembapanUdaraChart"></canvas>
                            </div>
                        </div>
                        <div class="lg:col-span-2">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between mb-3">
                                <h3 class="text-sm font-semibold text-gray-500 flex items-center">
                                    <span class="w-3 h-3 bg-orange-500 rounded-full mr-2"></span>
                                    Suhu (°C)
                                </h3>
                                <div class="text-xs bg-orange-100 text-orange-800 px-2 py-1 rounded-full w-fit">Kisaran Ideal:
                                    20-30°C</div>
                            </div>
                            <div class="bg-white rounded-xl p-4 shadow-inner border border-gray-100 h-64">
                                <canvas id="suhuChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kontrol Manual -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl overflow-hidden border border-white/20">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-xl font-bold text-gray-700">Kontrol Manual</h2>
                            <p class="text-sm text-gray-500 mt-1">Kendali perangkat kebun</p>
                        </div>
                        <div class="w-11 h-11 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center">
                            <i class="fas fa-sliders-h"></i>
                        </div>
                    </div>

                    <div class="space-y-5">
                        <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-inner">
                            <div class="flex items-center justify-between gap-3 mb-4">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-700">Penyiraman Manual</h3>
                                    <p class="text-xs text-gray-500 mt-1">Status: <span id="wateringManualStatus">OFF</span></p>
                                </div>
                                <div id="wateringManualIcon"
                                    class="w-10 h-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center">
                                    <i class="fas fa-tint"></i>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button" data-manual-group="watering" data-manual-value="ON"
                                    class="manual-control-btn px-3 py-2 rounded-lg text-sm font-semibold bg-gray-100 text-gray-600 hover:bg-gray-200">
                                    ON
                                </button>
                                <button type="button" data-manual-group="watering" data-manual-value="OFF"
                                    class="manual-control-btn active px-3 py-2 rounded-lg text-sm font-semibold bg-red-500 text-white hover:bg-red-600">
                                    OFF
                                </button>
                            </div>
                        </div>

                        <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-inner">
                            <div class="flex items-center justify-between gap-3 mb-4">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-700">Atap Manual</h3>
                                    <p class="text-xs text-gray-500 mt-1">Status: <span id="roofManualStatus">Tertutup</span></p>
                                </div>
                                <div id="roofManualIcon"
                                    class="w-10 h-10 rounded-full bg-green-100 text-green-600 flex items-center justify-center">
                                    <i class="fas fa-warehouse"></i>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button" data-manual-group="roof" data-manual-value="Buka"
                                    class="manual-control-btn px-3 py-2 rounded-lg text-sm font-semibold bg-gray-100 text-gray-600 hover:bg-gray-200">
                                    Buka
                                </button>
                                <button type="button" data-manual-group="roof" data-manual-value="Tutup"
                                    class="manual-control-btn active px-3 py-2 rounded-lg text-sm font-semibold bg-green-600 text-white hover:bg-green-700">
                                    Tutup
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bagian Riwayat Data -->
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl overflow-hidden border border-white/20">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-gray-700">Riwayat Data Sensor</h2>
                    <div class="flex space-x-3">
                        <button
                            class="px-4 py-2 bg-gray-100 rounded-lg text-sm text-gray-600 hover:bg-gray-200 flex items-center">
                            <i class="fas fa-filter mr-2"></i> Filter
                        </button>
                        <button
                            class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-emerald-700 flex items-center transition-all hover:shadow-lg"
                            id="exportExcelBtn">
                            <i class="fas fa-file-excel mr-2"></i> Ekspor Excel
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto rounded-xl border border-gray-100 shadow-inner">
                    <table class="min-w-full divide-y divide-gray-200 text-center">
                        <thead class="bg-gray-50 text-left">
                            <tr>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal &
                                    Waktu</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Kelembapan
                                    Tanah</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Kelembapan
                                    Udara</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Suhu</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Status
                                    Hujan</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Prediksi
                                    Kelembapan</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Status
                                    Prediksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100 text-left">
                            @foreach ($riwayat as $data)
                                @php
                                    $dataPrediksi = $prediksi[$loop->index] ?? null;
                                @endphp
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ \Carbon\Carbon::parse($data['tanggal'])->format('d M Y') }}</div>
                                        <div class="text-xs text-gray-500">
                                            {{ \Carbon\Carbon::parse($data['tanggal'])->format('H:i') }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div
                                                class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center mr-2">
                                                <i class="fas fa-seedling text-green-700 text-xs"></i>
                                            </div>
                                            <span
                                                class="text-sm font-medium text-green-700">{{ $data['kelembapan'] }}%</span>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div
                                                class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center mr-2">
                                                <i class="fas fa-wind text-blue-500 text-xs"></i>
                                            </div>
                                            <span
                                                class="text-sm font-medium text-blue-600">{{ $data['kelembapan_udara'] }}%</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div
                                                class="w-8 h-8 rounded-full bg-yellow-100 flex items-center justify-center mr-2">
                                                <i class="fas fa-thermometer-half text-yellow-600 text-xs"></i>
                                            </div>
                                            <span class="text-sm font-medium text-yellow-700">{{ $data['suhu'] }}°C</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="px-3 py-1 inline-flex items-center justify-center gap-1.5 text-xs leading-5 font-semibold rounded-full min-w-[76px]
                                    {{ $data['hujan'] ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                                            <i class="fas {{ $data['hujan'] ? 'fa-cloud-rain' : 'fa-sun' }} text-xs"></i>
                                            {{ $data['hujan'] ? 'Hujan' : 'Cerah' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($dataPrediksi)
                                            <div class="flex items-center">
                                                <div
                                                    class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center mr-2">
                                                    <i class="fas fa-tint text-blue-600 text-xs"></i>
                                                </div>
                                                <span
                                                    class="text-sm font-medium text-blue-600">{{ $dataPrediksi['kelembapan_prediksi'] }}%</span>
                                            </div>
                                        @else
                                            <span class="text-sm text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($dataPrediksi)
                                            <span
                                                class="px-3 py-1 inline-flex items-center justify-center gap-1.5 text-xs leading-5 font-semibold rounded-full min-w-[96px]
                                                {{ $dataPrediksi['status'] == 'Siram' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                                                <i
                                                    class="fas {{ $dataPrediksi['status'] == 'Siram' ? 'fa-faucet' : 'fa-check-circle' }} text-xs"></i>
                                                {{ $dataPrediksi['status'] }}
                                            </span>
                                        @else
                                            <span class="text-sm text-gray-400">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 flex items-center justify-between">
                    <div class="text-sm text-gray-500">
                        Menampilkan <span class="font-medium">1</span> sampai <span class="font-medium">6</span> dari
                        <span class="font-medium">6</span> entri
                    </div>
                    <div class="flex space-x-2">
                        <button
                            class="px-3 py-1 bg-gray-100 rounded-lg text-sm text-gray-600 hover:bg-gray-200 disabled:opacity-50"
                            disabled>
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="px-3 py-1 bg-emerald-600 text-white rounded-lg text-sm hover:bg-emerald-700">
                            1
                        </button>
                        <button
                            class="px-3 py-1 bg-gray-100 rounded-lg text-sm text-gray-600 hover:bg-gray-200 disabled:opacity-50"
                            disabled>
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Grafik Kelembapan Tanah
            const kelembapanCtx = document.getElementById('kelembapanChart').getContext('2d');
            const kelembapanGradient = kelembapanCtx.createLinearGradient(0, 0, 0, 150);
            kelembapanGradient.addColorStop(0, 'rgba(59, 130, 246, 0.3)');
            kelembapanGradient.addColorStop(1, 'rgba(59, 130, 246, 0.1)');
            new Chart(kelembapanCtx, {
                type: 'line',
                data: {
                    labels: @json($grafik['labels']),
                    datasets: [{
                        label: 'Kelembapan Tanah',
                        data: @json($grafik['kelembapan']),
                        backgroundColor: kelembapanGradient,
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: 'white',
                        pointBorderColor: 'rgba(59, 130, 246, 1)',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            min: 50,
                            max: 100,
                            grid: {
                                drawBorder: false,
                                color: 'rgba(0,0,0,0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false
                            }
                        }
                    }
                }
            });

            // Grafik Kelembapan Udara
            const kelembapanUdaraCtx = document.getElementById('kelembapanUdaraChart').getContext('2d');
            const kelembapanUdaraGradient = kelembapanUdaraCtx.createLinearGradient(0, 0, 0, 150);
            kelembapanUdaraGradient.addColorStop(0, 'rgba(34, 197, 94, 0.3)');
            kelembapanUdaraGradient.addColorStop(1, 'rgba(34, 197, 94, 0.1)');
            new Chart(kelembapanUdaraCtx, {
                type: 'line',
                data: {
                    labels: @json($grafik['labels']),
                    datasets: [{
                        label: 'Kelembapan Udara',
                        data: @json($grafik['kelembapan_udara']),
                        backgroundColor: kelembapanUdaraGradient,
                        borderColor: 'rgba(34, 197, 94, 1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: 'white',
                        pointBorderColor: 'rgba(34, 197, 94, 1)',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            min: 40,
                            max: 100,
                            grid: {
                                drawBorder: false,
                                color: 'rgba(0,0,0,0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false
                            }
                        }
                    }
                }
            });

            // Grafik Suhu
            const suhuCtx = document.getElementById('suhuChart').getContext('2d');
            const suhuGradient = suhuCtx.createLinearGradient(0, 0, 0, 150);
            suhuGradient.addColorStop(0, 'rgba(249, 115, 22, 0.3)');
            suhuGradient.addColorStop(1, 'rgba(249, 115, 22, 0.1)');
            new Chart(suhuCtx, {
                type: 'line',
                data: {
                    labels: @json($grafik['labels']),
                    datasets: [{
                        label: 'Suhu',
                        data: @json($grafik['suhu']),
                        backgroundColor: suhuGradient,
                        borderColor: 'rgba(249, 115, 22, 1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: 'white',
                        pointBorderColor: 'rgba(249, 115, 22, 1)',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            min: 20,
                            max: 40,
                            grid: {
                                drawBorder: false,
                                color: 'rgba(0,0,0,0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false
                            }
                        }
                    }
                }
            });

            // Fungsi tombol refresh
            const refreshBtn = document.querySelector('button.bg-green-700');
            if (refreshBtn) {
                refreshBtn.addEventListener('click', function() {
                    window.location.reload();
                });
            }

            // Fungsi tombol unduh Excel
            const exportExcelBtn = document.getElementById('exportExcelBtn');
            if (exportExcelBtn) {
                exportExcelBtn.addEventListener('click', function() {
                    alert(
                        'Fungsi unduh Excel akan diimplementasikan di sini\\n(Data akan diekspor dalam format .xlsx)');
                });
            }

            const manualStatuses = {
                watering: document.getElementById('wateringManualStatus'),
                roof: document.getElementById('roofManualStatus')
            };
            const wateringManualIcon = document.getElementById('wateringManualIcon');
            const roofManualIcon = document.getElementById('roofManualIcon');
            const manualButtonColors = [
                'bg-gray-100', 'bg-blue-600', 'bg-red-500', 'bg-yellow-500', 'bg-green-600',
                'text-gray-600', 'text-white', 'hover:bg-gray-200', 'hover:bg-blue-700',
                'hover:bg-red-600', 'hover:bg-yellow-600', 'hover:bg-green-700'
            ];
            const manualActiveColors = {
                watering: {
                    ON: ['bg-blue-600', 'text-white', 'hover:bg-blue-700'],
                    OFF: ['bg-red-500', 'text-white', 'hover:bg-red-600']
                },
                roof: {
                    Buka: ['bg-yellow-500', 'text-white', 'hover:bg-yellow-600'],
                    Tutup: ['bg-green-600', 'text-white', 'hover:bg-green-700']
                }
            };
            const manualInactiveColors = ['bg-gray-100', 'text-gray-600', 'hover:bg-gray-200'];

            document.querySelectorAll('.manual-control-btn').forEach(function(button) {
                button.addEventListener('click', function() {
                    const group = button.dataset.manualGroup;
                    const value = button.dataset.manualValue;

                    Swal.fire({
                        title: 'Konfirmasi Tindakan',
                        text: `Apakah Anda yakin ingin mengubah ${group} menjadi ${value}?`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#059669',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Ya, Lanjutkan!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Logic update state
                            document.querySelectorAll(`[data-manual-group="${group}"]`).forEach(function(groupButton) {
                                const isActive = groupButton === button;
                                groupButton.classList.toggle('active', isActive);
                                groupButton.classList.remove(...manualButtonColors);
                                groupButton.classList.add(...(isActive
                                    ? manualActiveColors[group][groupButton.dataset.manualValue]
                                    : manualInactiveColors));
                            });

                            if (manualStatuses[group]) {
                                manualStatuses[group].textContent = value === 'Buka' ? 'Terbuka' : (value === 'Tutup' ? 'Tertutup' : value);
                            }

                            if (group === 'watering' && wateringManualIcon) {
                                wateringManualIcon.classList.remove('bg-blue-100', 'text-blue-600', 'bg-red-100',
                                    'text-red-600');
                                wateringManualIcon.classList.add(...(value === 'ON' ? ['bg-blue-100', 'text-blue-600'] : [
                                    'bg-red-100', 'text-red-600'
                                ]));
                            }

                            if (group === 'roof' && roofManualIcon) {
                                roofManualIcon.classList.remove('bg-yellow-100', 'text-yellow-600', 'bg-green-100',
                                    'text-green-600');
                                roofManualIcon.classList.add(...(value === 'Buka' ? ['bg-yellow-100', 'text-yellow-600'] : [
                                    'bg-green-100', 'text-green-600'
                                ]));
                            }
                            
                            Swal.fire('Berhasil!', `Tindakan ${group} ke ${value} telah dikirim.`, 'success');
                        }
                    });
                });
            });

            // Toggle pompa
            let pompaState = {{ $pompa ? 'true' : 'false' }};

            function togglePompa() {
                pompaState = !pompaState;
                const indicator = document.getElementById('pompaIndicator');
                const status = document.getElementById('pompaStatus');
                if (pompaState) {
                    indicator.classList.remove('bg-gray-400');
                    indicator.classList.add('bg-green-400', 'animate-pulse');
                    status.classList.remove('bg-gray-500/20', 'text-gray-200');
                    status.classList.add('bg-green-500/20', 'text-green-200');
                    status.textContent = 'ON';
                } else {
                    indicator.classList.remove('bg-green-400', 'animate-pulse');
                    indicator.classList.add('bg-gray-400');
                    status.classList.remove('bg-green-500/20', 'text-green-200');
                    status.classList.add('bg-gray-500/20', 'text-gray-200');
                    status.textContent = 'OFF';
                }
            }

            // Toggle switch status
            const switchToggle = document.getElementById('switchToggle');
            const switchStatus = document.getElementById('switchStatus');
            const onIcon = document.getElementById('onIcon');
            const offIcon = document.getElementById('offIcon');

            function updateSwitchStatus() {
                if (switchToggle && switchToggle.checked) {
                    if (switchStatus) {
                        switchStatus.textContent = 'ACTIVE';
                        switchStatus.classList.remove('bg-gradient-to-r', 'from-rose-400', 'to-pink-500');
                        switchStatus.classList.add('bg-gradient-to-r', 'from-emerald-400', 'to-teal-500');
                    }
                } else {
                    if (switchStatus) {
                        switchStatus.textContent = 'INACTIVE';
                        switchStatus.classList.remove('bg-gradient-to-r', 'from-emerald-400', 'to-teal-500');
                        switchStatus.classList.add('bg-gradient-to-r', 'from-rose-400', 'to-pink-500');
                    }
                }
            }
            if (switchToggle) {
                switchToggle.addEventListener('change', updateSwitchStatus);
                updateSwitchStatus();
            }
        });
    </script>

    <style>
        @keyframes rain {
            to {
                transform: translateY(120px);
            }
        }

        .animate-rain {
            position: relative;
            height: 100%;
            width: 100%;
        }

        /* Transisi halus untuk semua elemen interaktif */
        button,
        .hover-effect {
            transition: all 0.3s ease;
        }

        /* Efek hover card */
        .hover-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        /* Scrollbar kustom */
        ::-webkit-scrollbar {
            height: 6px;
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #a1a1a1;
        }
    </style>
@endsection

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bonsai IoT Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Redirect ke halaman login jika belum login
        if (!localStorage.getItem('userEmail')) {
            window.location.href = '/';
        }

        function logout() {
            localStorage.removeItem('userEmail');
            window.location.href = '/';
        }
    </script>
</head>

<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Navbar -->
        <nav class="bg-green-600 text-white shadow-lg">
            <div class="container mx-auto px-4 py-3">
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-4">
                        <i class="fas fa-tree text-2xl"></i>
                        <h1 class="text-2xl font-bold">BonsAI</h1>
                    </div>
                    <div class="flex items-center space-x-2 md:space-x-4">
                        <span class="text-xs md:text-sm hidden sm:inline-block">Last updated: {{ now()->format('d M Y H:i') }}</span>
                        <button class="bg-green-700 hover:bg-green-800 px-3 md:px-4 py-2 rounded-lg flex items-center text-sm md:text-base">
                            <i class="fas fa-sync-alt mr-1 md:mr-2"></i> <span class="hidden md:inline">Refresh</span>
                        </button>
                        <button onclick="logout()" class="bg-red-600 hover:bg-red-700 px-3 md:px-4 py-2 rounded-lg flex items-center text-sm md:text-base transition">
                            <i class="fas fa-sign-out-alt mr-1 md:mr-2"></i> <span class="hidden md:inline">Logout</span>
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="container mx-auto px-4 py-6">
            @yield('content')
        </main>
    </div>
</body>

</html>

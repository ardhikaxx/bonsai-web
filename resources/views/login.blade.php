<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BonsAI</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        if (localStorage.getItem('userEmail')) {
            window.location.href = '/dashboard';
        }
    </script>
</head>
<body class="bg-gradient-to-br from-green-50 to-green-100 flex items-center justify-center min-h-screen p-4">
    <div class="bg-white p-8 md:p-10 rounded-2xl shadow-2xl w-full max-w-md border border-green-50">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 mb-4 text-green-600">
                <i class="fas fa-tree text-3xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 tracking-tight">BonsAI Login</h1>
            <p class="text-gray-500 mt-2">Masuk untuk memonitor kebun bonsai Anda</p>
        </div>

        <div class="space-y-5">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
                <input type="email" id="email" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors bg-gray-50 focus:bg-white" placeholder="admin@example.com">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Password</label>
                <input type="password" id="password" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors bg-gray-50 focus:bg-white" placeholder="••••••••">
            </div>
            <button id="emailLoginBtn" class="w-full bg-green-600 text-white font-bold py-3 px-4 rounded-xl hover:bg-green-700 active:transform active:scale-95 transition-all shadow-lg hover:shadow-green-500/30">
                Login
            </button>

            <div class="relative flex items-center py-3">
                <div class="flex-grow border-t border-gray-200"></div>
                <span class="flex-shrink-0 mx-4 text-gray-400 text-sm font-medium">ATAU</span>
                <div class="flex-grow border-t border-gray-200"></div>
            </div>

            <button id="googleLoginBtn" class="w-full bg-white border-2 border-gray-200 text-gray-700 font-bold py-3 px-4 rounded-xl hover:bg-gray-50 hover:border-gray-300 active:transform active:scale-95 transition-all flex items-center justify-center shadow-sm">
                <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="Google Logo" class="w-6 h-6 mr-3">
                Login with Google
            </button>
        </div>
    </div>

    <!-- Firebase SDK menggunakan ES Modules untuk browser -->
    <script type="module">
        import { initializeApp } from "https://www.gstatic.com/firebasejs/12.15.0/firebase-app.js";
        import { getAuth, signInWithEmailAndPassword, signInWithPopup, GoogleAuthProvider } from "https://www.gstatic.com/firebasejs/12.15.0/firebase-auth.js";
        import { getAnalytics } from "https://www.gstatic.com/firebasejs/12.15.0/firebase-analytics.js";

        const firebaseConfig = {
            apiKey: "AIzaSyAgjokNIX7UhQEmLhHWuSCo-K0uX_go0hE",
            authDomain: "skripsi1-7a46f.firebaseapp.com",
            databaseURL: "https://skripsi1-7a46f-default-rtdb.asia-southeast1.firebasedatabase.app",
            projectId: "skripsi1-7a46f",
            storageBucket: "skripsi1-7a46f.firebasestorage.app",
            messagingSenderId: "81924875777",
            appId: "1:81924875777:web:a5f10372e3ca4de952c200",
            measurementId: "G-95JH87702K"
        };

        // Initialize Firebase
        const app = initializeApp(firebaseConfig);
        const analytics = getAnalytics(app);
        const auth = getAuth(app);
        const provider = new GoogleAuthProvider();

        const emailLoginBtn = document.getElementById('emailLoginBtn');
        const googleLoginBtn = document.getElementById('googleLoginBtn');

        // Login Email & Password
        emailLoginBtn.addEventListener('click', () => {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            if (!email || !password) {
                Swal.fire('Error', 'Email dan password harus diisi', 'warning');
                return;
            }

            emailLoginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
            emailLoginBtn.disabled = true;

            signInWithEmailAndPassword(auth, email, password)
                .then((userCredential) => {
                    localStorage.setItem('userEmail', userCredential.user.email);
                    window.location.href = '/dashboard';
                })
                .catch((error) => {
                    Swal.fire('Login Gagal', 'Kredensial tidak valid atau salah.', 'error');
                    emailLoginBtn.innerHTML = 'Login';
                    emailLoginBtn.disabled = false;
                });
        });

        // Login Google
        googleLoginBtn.addEventListener('click', () => {
            // Disable button untuk mencegah double-click
            const originalContent = googleLoginBtn.innerHTML;
            googleLoginBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Memproses...';
            googleLoginBtn.disabled = true;

            signInWithPopup(auth, provider)
                .then((result) => {
                    const user = result.user;
                    
                    // Pengecekan email hanya untuk yang diizinkan
                    if (user.email === 'fayzatulhidayat21@gmail.com') {
                        localStorage.setItem('userEmail', user.email);
                        window.location.href = '/dashboard';
                    } else {
                        // Jika bukan email yang diizinkan, otomatis logout kembali
                        auth.signOut();
                        Swal.fire('Akses Ditolak', 'Hanya akun fayzatulhidayat21@gmail.com yang memiliki akses via Google.', 'error');
                        googleLoginBtn.innerHTML = originalContent;
                        googleLoginBtn.disabled = false;
                    }
                })
                .catch((error) => {
                    googleLoginBtn.innerHTML = originalContent;
                    googleLoginBtn.disabled = false;
                    
                    if (error.code !== 'auth/popup-closed-by-user' && error.code !== 'auth/cancelled-popup-request') {
                        Swal.fire('Login Gagal', error.message, 'error');
                    }
                });
        });
    </script>
</body>
</html>

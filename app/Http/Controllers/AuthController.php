<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    // 1. Menampilkan Halaman Login
    public function showLoginForm()
    {
        // Jika user sudah login (punya token), langsung lempar ke dashboard
        if (session()->has('jwt_token')) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    // 2. Proses Submit Form Login
    public function login(Request $request)
    {
        // Validasi input form dari browser
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $baseUrl = config('services.instapos.base_url');

        try {

            // Tembak API Login Node.JS
            $response = Http::withoutVerifying()->post("{$baseUrl}/auth/login", [
                'email'    => $request->email,
                'password' => $request->password,
            ]);

            if ($response->successful()) {
    $responseData = $response->json();

    // Helper data_get() akan mengecek semua kemungkinan lokasi token
    $token = data_get($responseData, 'data.access_token')
          ?? data_get($responseData, 'data.accessToken')
          ?? data_get($responseData, 'access_token')
          ?? data_get($responseData, 'token');

    $user = data_get($responseData, 'data.user')
         ?? data_get($responseData, 'user');

    // Jika token masih tidak ditemukan, dump seluruh isi JSON untuk melihat key aslinya
    if (!$token) {
        dd('Token tidak ditemukan di JSON. Isi respon asli:', $responseData);
    }

    session([
        'jwt_token' => $token,
        'user'      => $user,
    ]);

    session()->save();

    return redirect()->route('dashboard');
}

            // Jika Email/Password Salah (HTTP Status 400/401/422)
            $errorMessage = $response->json()['message'] ?? 'Email atau password salah.';
            return back()->withErrors(['login_error' => $errorMessage])->withInput();

        } catch (\Exception $e) {
            // Jika API Vercel mati / tidak bisa dihubungi
            return back()->withErrors(['login_error' => 'Gagal terhubung ke server API.'])->withInput();
        }
    }

    // 3. Proses Logout
    public function logout()
    {
        $baseUrl = config('services.instapos.base_url');
        $token   = session('jwt_token');

        try {
            // Tembak API Logout sambil membawa Bearer Token
            Http::withToken($token)->post("{$baseUrl}/auth/logout");
        } catch (\Exception $e) {
            // Tetap jalankan logout di laravel meskipun API error
        }

        // Hapus semua data session di Laravel
        session()->forget(['jwt_token', 'user']);
        session()->flush();

        return redirect()->route('login')->with('success', 'Anda telah logout.');
    }
}

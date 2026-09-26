<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PenjualanController extends Controller
{
    public function index()
    {
        return view('penjualan.index');
    }

    /**
     * Proxy API Produk ke Staging Vercel
     */
    public function getProducts()
    {
        // Ambil token presisi dari key 'jwt_token' sesuai LoginController
        $token = session('jwt_token');

        if (!$token) {
            return response()->json([
                'meta' => [
                    'status' => 401,
                    'message' => 'Token login tidak ditemukan di session. Silakan Re-Login.'
                ],
                'data' => []
            ], 401);
        }

        // Ambil base_url dari config (sama seperti di LoginController)
        $baseUrl = config('services.instapos.base_url', 'https://instapos-api-staging.vercel.app/api');

        try {
            $response = Http::withoutVerifying()
                ->withToken($token)
                ->acceptJson()
                ->get("{$baseUrl}/products");

            if ($response->successful()) {
                return response()->json($response->json(), $response->status());
            }

            return response()->json([
                'meta' => [
                    'status' => $response->status(),
                    'message' => $response->json('message') ?? $response->json('meta.message') ?? 'Gagal mengambil data produk dari API.'
                ],
                'data' => []
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json([
                'meta' => [
                    'status' => 500,
                    'message' => 'Gagal terhubung ke API Vercel: ' . $e->getMessage()
                ],
                'data' => []
            ], 500);
        }
    }
}

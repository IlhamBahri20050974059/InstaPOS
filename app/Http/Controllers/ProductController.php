<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ProductController extends Controller
{
    /**
     * Tampilkan Halaman Utama Manajemen Produk
     */
    public function index()
    {
        return view('products.index');
    }

    /**
     * Proxy untuk Ambil Semua Produk dari Vercel API
     */
    public function getProducts()
    {
        $token = session('jwt_token');
        $baseUrl = config('services.instapos.base_url', 'https://instapos-api-staging.vercel.app/api');

        try {
            $response = Http::withoutVerifying()
                ->withToken($token)
                ->acceptJson()
                ->get("{$baseUrl}/products");

            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'meta' => ['status' => 500, 'message' => 'Gagal terhubung ke API Produk: ' . $e->getMessage()],
                'data' => []
            ], 500);
        }
    }

    /**
     * Proxy untuk Ambil Semua Kategori
     */
    public function getCategories()
    {
        $token = session('jwt_token');
        $baseUrl = config('services.instapos.base_url', 'https://instapos-api-staging.vercel.app/api');

        try {
            $response = Http::withoutVerifying()
                ->withToken($token)
                ->acceptJson()
                ->get("{$baseUrl}/categories");

            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'meta' => ['status' => 500, 'message' => 'Gagal terhubung ke API Kategori: ' . $e->getMessage()],
                'data' => []
            ], 500);
        }
    }

    /**
     * Proxy untuk Tambah Produk Baru (POST)
     */
    public function store(Request $request)
    {
        $token = session('jwt_token');
        $baseUrl = config('services.instapos.base_url', 'https://instapos-api-staging.vercel.app/api');

        try {
            $response = Http::withoutVerifying()
                ->withToken($token)
                ->acceptJson()
                ->post("{$baseUrl}/products", [
                    'category_id' => $request->category_id,
                    'name'        => $request->name,
                    'price'       => (int) $request->price,
                    'barcode'     => (string) $request->barcode,
                ]);

            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'meta' => ['status' => 500, 'message' => 'Gagal menyimpan produk: ' . $e->getMessage()]
            ], 500);
        }
    }

    /**
     * Proxy untuk Update Produk (PATCH)
     */
    public function update(Request $request, $id)
    {
        $token = session('jwt_token');
        $baseUrl = config('services.instapos.base_url', 'https://instapos-api-staging.vercel.app/api');

        try {
            $payload = [
                'category_id' => $request->category_id,
                'name'        => $request->name,
                'price'       => (int) $request->price,
                'barcode'     => (string) $request->barcode,
            ];

            // Jika API mendukung toggle is_active pada patch
            if ($request->has('is_active')) {
                $payload['is_active'] = (bool) $request->is_active;
            }

            $response = Http::withoutVerifying()
                ->withToken($token)
                ->acceptJson()
                ->patch("{$baseUrl}/products/{$id}", $payload);

            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'meta' => ['status' => 500, 'message' => 'Gagal memperbarui produk: ' . $e->getMessage()]
            ], 500);
        }
    }
}

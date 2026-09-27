<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SupplierController extends Controller
{
    /**
     * Tampilkan Halaman Utama Supplier
     */
    public function index()
    {
        return view('supplier.index');
    }

    /**
     * Proxy untuk Ambil Semua Supplier (GET /api/suppliers)
     */
    public function getSuppliers()
    {
        $token = session('jwt_token');
        $baseUrl = config('services.instapos.base_url', 'https://instapos-api-staging.vercel.app/api');

        try {
            $response = Http::withoutVerifying()
                ->withToken($token)
                ->acceptJson()
                ->get("{$baseUrl}/suppliers");

            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'meta' => ['status' => 500, 'message' => 'Gagal terhubung ke API Supplier: ' . $e->getMessage()],
                'data' => []
            ], 500);
        }
    }

    /**
     * Proxy untuk Tambah Supplier Baru (POST /api/suppliers)
     */
    public function store(Request $request)
    {
        $token = session('jwt_token');
        $baseUrl = config('services.instapos.base_url', 'https://instapos-api-staging.vercel.app/api');

        try {
            $response = Http::withoutVerifying()
                ->withToken($token)
                ->acceptJson()
                ->post("{$baseUrl}/suppliers", [
                    'code'         => (string) $request->code,
                    'name'         => (string) $request->name,
                    'phone_number' => (string) $request->phone_number,
                    'address'      => (string) $request->address,
                ]);

            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'meta' => ['status' => 500, 'message' => 'Gagal menyimpan supplier: ' . $e->getMessage()]
            ], 500);
        }
    }

    /**
     * Proxy untuk Update Supplier (PATCH /api/suppliers/{id})
     */
    public function update(Request $request, $id)
    {
        $token = session('jwt_token');
        $baseUrl = config('services.instapos.base_url', 'https://instapos-api-staging.vercel.app/api');

        try {
            $response = Http::withoutVerifying()
                ->withToken($token)
                ->acceptJson()
                ->patch("{$baseUrl}/suppliers/{$id}", [
                    'code'         => (string) $request->code,
                    'name'         => (string) $request->name,
                    'phone_number' => (string) $request->phone_number,
                    'address'      => (string) $request->address,
                ]);

            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'meta' => ['status' => 500, 'message' => 'Gagal memperbarui supplier: ' . $e->getMessage()]
            ], 500);
        }
    }
}

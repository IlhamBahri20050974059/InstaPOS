<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class KategoriController extends Controller
{
    protected $apiUrl;

    public function __construct()
    {
        // Sesuaikan dengan URL base API backend kamu
        $this->apiUrl = config('services.api.base_url', env('API_BASE_URL', 'http://localhost:8000/api'));
    }

    /**
     * Tampilkan halaman utama Kategori
     */
    public function index()
    {
        return view('kategori.index');
    }

    /**
     * Ambil semua data kategori (GET /api/categories)
     */
    public function data()
    {
        try {
            $response = Http::get("{$this->apiUrl}/categories"); //[cite: 5]
            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'meta' => ['message' => 'Gagal terhubung ke API backend'],
                'data' => []
            ], 500);
        }
    }

    /**
     * Tambah kategori baru (POST /api/categories)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        try {
            $response = Http::post("{$this->apiUrl}/categories", $validated); //[cite: 5]
            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'meta' => ['message' => 'Gagal menyimpan data kategori ke server']
            ], 500);
        }
    }

    /**
     * Perbarui data kategori (PATCH /api/categories/{id})
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        try {
            $response = Http::patch("{$this->apiUrl}/categories/{$id}", $validated); //[cite: 5]
            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'meta' => ['message' => 'Gagal memperbarui data kategori']
            ], 500);
        }
    }
}

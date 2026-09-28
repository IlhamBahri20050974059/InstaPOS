<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf; // Pastikan package barryvdh/laravel-dompdf terinstall, atau gunakan alternatif view print

class RiwayatPenjualanController extends Controller
{
    protected $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('services.api.base_url', env('API_BASE_URL', 'http://localhost:8000/api'));
    }

    /**
     * Tampilkan Halaman Utama Riwayat Penjualan
     */
    public function index()
    {
        return view('riwayat-penjualan.index');
    }

    /**
     * Ambil Data Riwayat Penjualan dari API Backend
     */
    public function data(Request $request)
    {
        try {
            // Penerusan filter tanggal dan pencarian jika backend mendukung query params
            $queryParams = array_filter([
                'start_date' => $request->query('start_date'),
                'end_date'   => $request->query('end_date'),
                'search'     => $request->query('search'),
                'page'       => $request->query('page', 1),
            ]);

            $response = Http::get("{$this->apiUrl}/sales", $queryParams);
            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'meta' => ['message' => 'Gagal terhubung ke server API'],
                'data' => []
            ], 500);
        }
    }

    /**
     * Cetak Struk Penjualan Format PDF
     */
    public function cetakPdf($id)
    {
        try {
            // Ambil data transaksi dari backend API
            $response = Http::get("{$this->apiUrl}/sales/{$id}");

            if (!$response->successful()) {
                return back()->with('error', 'Gagal mengambil data transaksi untuk cetak PDF.');
            }

            $result = $response->json();
            $transaksi = $result['data'] ?? $result;

            // Jika menggunakan package dompdf:
            if (class_exists('Barryvdh\DomPDF\Facade\Pdf')) {
                $pdf = Pdf::loadView('riwayat-penjualan.struk-pdf', compact('transaksi'))
                          ->setPaper([0, 0, 226.77, 500], 'portrait'); // Ukuran kertas thermal 80mm / 58mm
                return $pdf->stream("Struk-{$transaksi['invoice_number']}.pdf");
            }

            // Fallback jika belum install DomPDF: tampilkan view HTML printable secara langsung
            return view('riwayat-penjualan.struk-pdf', compact('transaksi'));

        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan saat memproses PDF: ' . $e->getMessage());
        }
    }
}

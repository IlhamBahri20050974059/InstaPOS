<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class RiwayatPenjualanController extends Controller
{
    protected $apiUrl;

    public function __construct()
    {
        // Samakan key config Base URL dengan AuthController
        $this->apiUrl = config('services.instapos.base_url', env('API_BASE_URL', 'http://localhost:8000/api'));
    }

    /**
     * Tampilkan Halaman Utama Riwayat Penjualan
     */
    public function index()
    {
        return view('riwayat-penjualan.index');
    }

    /**
     * Ambil Data Riwayat Penjualan dari API Node.js Backend
     */
    public function data(Request $request)
    {
        try {
            // Ambil token langsung dari session 'jwt_token'
            $token = session('jwt_token');

            if (!$token) {
                return response()->json([
                    'meta' => [
                        'status' => 401,
                        'message' => 'Token JWT tidak ditemukan dalam session Laravel'
                    ],
                    'data' => []
                ], 401);
            }

            $queryParams = array_filter([
                'start_date' => $request->query('start_date'),
                'end_date'   => $request->query('end_date'),
                'search'     => $request->query('search'),
                'page'       => $request->query('page', 1),
            ]);

            // Hit API Node.js backend dengan Bearer Token & SSL Bypass
            $response = Http::acceptJson()
                ->withToken($token)
                ->withoutVerifying()
                ->get("{$this->apiUrl}/sales", $queryParams);

            if ($response->failed()) {
                Log::error("API Backend Sales Error Status [{$response->status()}]: " . $response->body());
                return response()->json($response->json(), $response->status());
            }

            return response()->json($response->json(), 200);

        } catch (\Exception $e) {
            Log::error("Exception di RiwayatPenjualanController@data: " . $e->getMessage());

            return response()->json([
                'meta' => [
                    'status' => 500,
                    'message' => 'Terjadi kesalahan server: ' . $e->getMessage()
                ],
                'data' => []
            ], 500);
        }
    }

    /**
     * Cetak Struk Penjualan Format PDF
     */
    /**
 * Cetak Struk Penjualan PDF dari Halaman Riwayat
 */
public function cetakPdf($id)
{
    try {
        $token = session('jwt_token');

        if (!$token) {
            return back()->with('error', 'Sesi login telah berakhir.');
        }

        // Ambil rincian data transaksi dari API
        $response = Http::acceptJson()
            ->withToken($token)
            ->withoutVerifying()
            ->get("{$this->apiUrl}/sales/{$id}");

        if (!$response->successful()) {
            return back()->with('error', 'Gagal mengambil data transaksi dari server.');
        }

        $result = $response->json();
        $transaksi = $result['data'] ?? $result;

        // Gunakan view 'penjualan.struk-pdf' agar tampilan 100% sama dengan di POS Kasir
        if (class_exists('Barryvdh\DomPDF\Facade\Pdf')) {
            $pdf = Pdf::loadView('penjualan.struk-pdf', compact('transaksi'))
                      ->setPaper([0, 0, 226.77, 500], 'portrait');
            return $pdf->stream("Struk-{$transaksi['invoice_number']}.pdf");
        }

        return view('penjualan.struk-pdf', compact('transaksi'));

    } catch (\Exception $e) {
        return back()->with('error', 'Terjadi kesalahan saat memproses PDF: ' . $e->getMessage());
    }
}
}

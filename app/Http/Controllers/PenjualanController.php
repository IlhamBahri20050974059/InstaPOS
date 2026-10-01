<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class PenjualanController extends Controller
{
    protected $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('services.instapos.base_url', env('API_BASE_URL', 'http://localhost:8000/api'));
    }

    /**
     * Tampilan Halaman POS Penjualan
     */
    public function index()
    {
        return view('penjualan.index');
    }

    /**
     * Fetch Daftar Produk Aktif untuk Kasir
     */
    public function getProducts(Request $request)
    {
        try {
            $token = session('jwt_token');

            $response = Http::acceptJson()
                ->withToken($token)
                ->withoutVerifying()
                ->get("{$this->apiUrl}/products");

            if ($response->failed()) {
                return response()->json(['data' => []], $response->status());
            }

            return response()->json($response->json(), 200);
        } catch (\Exception $e) {
            return response()->json(['data' => []], 500);
        }
    }

    /**
     * Fetch Daftar Pelanggan/Member (Opsional)
     */
    public function getCustomers(Request $request)
    {
        try {
            $token = session('jwt_token');

            $response = Http::acceptJson()
                ->withToken($token)
                ->withoutVerifying()
                ->get("{$this->apiUrl}/customers");

            if ($response->failed()) {
                return response()->json(['data' => []], 200);
            }

            return response()->json($response->json(), 200);
        } catch (\Exception $e) {
            return response()->json(['data' => []], 200);
        }
    }

    /**
     * Simpan Transaksi Penjualan ke Server API Backend
     */
    public function store(Request $request)
    {
        // Validasi struktur input dari frontend
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'payments' => 'required|array|min:1',
            'payments.*.method' => 'required|string',
            'payments.*.amount' => 'required|numeric|min:0',
            'customer_id' => 'nullable|string',
        ]);

        try {
            $token = session('jwt_token');

            if (!$token) {
                return response()->json([
                    'meta' => ['status' => 401, 'message' => 'Sesi login telah berakhir. Sila login kembali.']
                ], 401);
            }

            // Konstruksi Payload sesuai kontrak API Backend Node.js
            $payload = [
                'items' => array_map(function ($item) {
                    return [
                        'product_id' => $item['product_id'],
                        'quantity'   => (int) $item['quantity'],
                    ];
                }, $request->items),

                'payments' => array_map(function ($payment) {
    return [
        'method' => strtolower($payment['method']),
        'amount' => (float) $payment['amount'], // Ganti (numeric) menjadi (float)
    ];
}, $request->payments),
            ];

            // Masukkan customer_id hanya jika diisi (opsional)
            if (!empty($request->customer_id)) {
                $payload['customer_id'] = $request->customer_id;
            }

            // Tembak POST /api/sales
            $response = Http::acceptJson()
                ->withToken($token)
                ->withoutVerifying()
                ->post("{$this->apiUrl}/sales", $payload);

            if ($response->failed()) {
                Log::error("Gagal Simpan Penjualan [Status {$response->status()}]: " . $response->body());
                return response()->json($response->json(), $response->status());
            }

            return response()->json($response->json(), 201);

        } catch (\Exception $e) {
            Log::error("Exception PenjualanController@store: " . $e->getMessage());
            return response()->json([
                'meta' => ['status' => 500, 'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()]
            ], 500);
        }
    }

    /**
     * Cetak Struk Penjualan PDF
     */
    public function cetakPdf($id)
    {
        try {
            $token = session('jwt_token');

            $response = Http::acceptJson()
                ->withToken($token)
                ->withoutVerifying()
                ->get("{$this->apiUrl}/sales/{$id}");

            if (!$response->successful()) {
                return back()->with('error', 'Gagal mengambil data transaksi dari server.');
            }

            $result = $response->json();
            $transaksi = $result['data'] ?? $result;

            if (class_exists('Barryvdh\DomPDF\Facade\Pdf')) {
                $pdf = Pdf::loadView('penjualan.struk-pdf', compact('transaksi'))
                          ->setPaper([0, 0, 226.77, 500], 'portrait');
                return $pdf->stream("Struk-{$transaksi['invoice_number']}.pdf");
            }

            return view('penjualan.struk-pdf', compact('transaksi'));

        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi ralat cetak PDF: ' . $e->getMessage());
        }
    }
}

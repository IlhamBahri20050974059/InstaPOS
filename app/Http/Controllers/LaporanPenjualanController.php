<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LaporanPenjualanController extends Controller
{
    protected $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('services.instapos.base_url', env('API_BASE_URL', 'http://localhost:8000/api'));
    }

    public function index()
    {
        return view('laporan.penjualan.index');
    }

    /**
     * Ambil data laporan dalam format JSON untuk Alpine.js
     */
    public function data(Request $request)
    {
        try {
            $token = session('jwt_token');

            if (!$token) {
                return response()->json([
                    'meta' => ['status' => 401, 'message' => 'Token JWT tidak ditemukan.']
                ], 401);
            }

            $from = $request->query('from', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $to   = $request->query('to', Carbon::now()->addDay()->format('Y-m-d'));
            $cashierId = $request->query('cashier_id');

            $queryParams = [
                'from' => $from,
                'to'   => $to,
            ];

            if ($cashierId) {
                $queryParams['cashier_id'] = $cashierId;
            }

            // Panggil API Vercel /api/report/sales
            $response = Http::acceptJson()
                ->withToken($token)
                ->withoutVerifying()
                ->get("{$this->apiUrl}/report/sales", $queryParams);

            if ($response->failed()) {
                Log::error("Laporan Penjualan Error [{$response->status()}]: " . $response->body());
                return response()->json($response->json(), $response->status());
            }

            return response()->json($response->json(), 200);

        } catch (\Exception $e) {
            Log::error("Exception di LaporanPenjualanController@data: " . $e->getMessage());

            return response()->json([
                'meta' => ['status' => 500, 'message' => 'Gagal terhubung ke API Laporan: ' . $e->getMessage()]
            ], 500);
        }
    }

    /**
     * Ekspor Laporan ke File PDF (A4 Ready)
     */
    public function exportPdf(Request $request)
    {
        try {
            $token = session('jwt_token');
            $from = $request->query('from', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $to   = $request->query('to', Carbon::now()->addDay()->format('Y-m-d'));
            $cashierId = $request->query('cashier_id');

            $queryParams = ['from' => $from, 'to' => $to];
            if ($cashierId) $queryParams['cashier_id'] = $cashierId;

            $response = Http::acceptJson()
                ->withToken($token)
                ->withoutVerifying()
                ->get("{$this->apiUrl}/report/sales", $queryParams);

            if (!$response->successful()) {
                return back()->with('error', 'Gagal mengambil data laporan dari server.');
            }

            $reportData = $response->json('data') ?? [];

            if (class_exists('Barryvdh\DomPDF\Facade\Pdf')) {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('laporan.penjualan.pdf', compact('reportData', 'from', 'to'))
                    ->setPaper('a4', 'portrait');
                return $pdf->stream("Laporan_Penjualan_{$from}_sd_{$to}.pdf");
            }

            return view('laporan.penjualan.pdf', compact('reportData', 'from', 'to'));

        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan ekspor PDF: ' . $e->getMessage());
        }
    }

    /**
     * Ekspor Laporan ke File CSV (Excel Compatible)
     */
    public function exportCsv(Request $request)
    {
        $token = session('jwt_token');
        $from = $request->query('from', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $to   = $request->query('to', Carbon::now()->addDay()->format('Y-m-d'));
        $cashierId = $request->query('cashier_id');

        $queryParams = ['from' => $from, 'to' => $to];
        if ($cashierId) $queryParams['cashier_id'] = $cashierId;

        $response = Http::acceptJson()
            ->withToken($token)
            ->withoutVerifying()
            ->get("{$this->apiUrl}/report/sales", $queryParams);

        $reportData = $response->json('data') ?? [];
        $dailyTrend = $reportData['daily_trend'] ?? [];
        $summary = $reportData['summary'] ?? [];

        $filename = "Laporan_Penjualan_{$from}_sd_{$to}.csv";

        $headers = [
            "Content-Type" => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=\"$filename\"",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function() use ($summary, $dailyTrend, $from, $to) {
            $file = fopen('php://output', 'w');

            // BOM UTF-8 agar karakter bahasa/angka terbaca rapi di Microsoft Excel
            fputs($file, "\xEF\xBB\xBF");

            // Header Informasi Laporan
            fputcsv($file, ['LAPORAN PENJUALAN - TOKO INSTAPOS']);
            fputcsv($file, ['Periode', "$from s/d $to"]);
            fputcsv($file, []);

            // Ringkasan Finansial
            fputcsv($file, ['--- RINGKASAN FINANSIAL ---']);
            fputcsv($file, ['Penjualan Kotor (Gross)', $summary['gross_sales'] ?? 0]);
            fputcsv($file, ['Total Diskon', $summary['total_discounts'] ?? 0]);
            fputcsv($file, ['Penjualan Bersih (Net)', $summary['net_sales'] ?? 0]);
            fputcsv($file, ['Total Transaksi', $summary['transaction_count'] ?? 0]);
            fputcsv($file, ['Rata-rata Transaksi', $summary['average_transaction_value'] ?? 0]);
            fputcsv($file, ['Total Produk Terjual (Pcs)', $summary['items_sold'] ?? 0]);
            fputcsv($file, []);

            // Tabel Tren Harian
            fputcsv($file, ['--- TREN PENJUALAN HARIAN ---']);
            fputcsv($file, ['Tanggal', 'Jumlah Transaksi', 'Penjualan Kotor (Rp)', 'Penjualan Bersih (Rp)']);

            foreach ($dailyTrend as $row) {
                fputcsv($file, [
                    $row['date'] ?? '-',
                    $row['transactions'] ?? 0,
                    $row['gross_sales'] ?? 0,
                    $row['net_sales'] ?? 0,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

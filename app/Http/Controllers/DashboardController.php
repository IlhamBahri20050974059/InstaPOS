<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DashboardController extends Controller
{
    protected $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('services.instapos.base_url', env('API_BASE_URL', 'http://localhost:8000/api'));
    }

    public function index()
    {
        return view('dashboard');
    }

    public function data(Request $request)
    {
        try {
            $token = session('jwt_token');

            if (!$token) {
                return response()->json([
                    'meta' => ['status' => 401, 'message' => 'Token JWT tidak ditemukan.']
                ], 401);
            }

            // Default tanggal: Awal bulan ini s/d besok (karena parameter 'to' bersifat exclusive)
            $defaultFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
            $defaultTo = Carbon::now()->addDay()->format('Y-m-d');

            $from = $request->query('from', $defaultFrom);
            $to = $request->query('to', $defaultTo);

            $response = Http::acceptJson()
                ->withToken($token)
                ->withoutVerifying()
                ->get("{$this->apiUrl}/dashboard/stats", [
                    'from' => $from,
                    'to'   => $to,
                ]);

            if ($response->failed()) {
                Log::error("Dashboard Stats Error [{$response->status()}]: " . $response->body());
                return response()->json($response->json(), $response->status());
            }

            return response()->json($response->json(), 200);

        } catch (\Exception $e) {
            Log::error("Exception di DashboardController@data: " . $e->getMessage());

            return response()->json([
                'meta' => ['status' => 500, 'message' => 'Gagal terhubung ke API Dashboard: ' . $e->getMessage()]
            ], 500);
        }
    }
}

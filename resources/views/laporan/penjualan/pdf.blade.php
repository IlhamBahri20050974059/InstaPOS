<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Penjualan - InstaPOS</title>
    <style>
        @page { size: A4 portrait; margin: 20mm; }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }

        .header {
            border-b: 2px solid #059669;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .header h1 {
            font-size: 18px;
            margin: 0;
            color: #059669;
        }
        .header p { margin: 2px 0; color: #666; font-size: 10px; }

        .summary-box {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .summary-box td {
            padding: 8px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            width: 25%;
        }
        .summary-title { font-size: 9px; color: #64748b; text-transform: uppercase; font-weight: bold; }
        .summary-value { font-size: 13px; font-weight: bold; color: #0f172a; margin-top: 3px; }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.data-table th {
            background: #059669;
            color: #fff;
            padding: 6px;
            font-size: 10px;
            text-align: left;
        }
        table.data-table td {
            padding: 6px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 10px;
        }

        .signatures {
            margin-top: 40px;
            width: 100%;
        }
        .signatures td {
            width: 50%;
            text-align: center;
        }
    </style>
</head>
<body>

    <!-- Header Dokumen -->
    <div class="header text-center">
        <h1>TOKO INSTAPOS MINIMARKET</h1>
        <p>Jl. Raya Utama Toko No. 123 | Telp: 0812-3456-7890</p>
        <p class="bold" style="margin-top: 8px; color: #000; font-size: 12px;">LAPORAN PENJUALAN</p>
        <p>Periode: {{ date('d/m/Y', strtotime($from)) }} s/d {{ date('d/m/Y', strtotime('-1 day', strtotime($to))) }}</p>
    </div>

    <!-- Summary Cards -->
    @php
        $summary = $reportData['summary'] ?? [];
        $dailyTrend = $reportData['daily_trend'] ?? [];
        $paymentBreakdown = $reportData['payment_breakdown'] ?? [];
        $cashiers = $reportData['cashiers'] ?? [];
    @endphp

    <table class="summary-box">
        <tr>
            <td>
                <div class="summary-title">Penjualan Bersih</div>
                <div class="summary-value" style="color: #059669;">Rp {{ number_format($summary['net_sales'] ?? 0, 0, ',', '.') }}</div>
            </td>
            <td>
                <div class="summary-title">Total Diskon</div>
                <div class="summary-value" style="color: #dc2626;">Rp {{ number_format($summary['total_discounts'] ?? 0, 0, ',', '.') }}</div>
            </td>
            <td>
                <div class="summary-title">Total Transaksi</div>
                <div class="summary-value">{{ number_format($summary['transaction_count'] ?? 0) }} Nota</div>
            </td>
            <td>
                <div class="summary-title">Produk Terjual</div>
                <div class="summary-value">{{ number_format($summary['items_sold'] ?? 0) }} Pcs</div>
            </td>
        </tr>
    </table>

    <!-- Tabel Tren Harian -->
    <h3 style="margin-bottom: 6px; font-size: 12px;">Rincian Transaksi Harian</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th class="text-center">Jumlah Transaksi</th>
                <th class="text-right">Penjualan Kotor</th>
                <th class="text-right">Penjualan Bersih</th>
            </tr>
        </thead>
        <tbody>
            @forelse($dailyTrend as $row)
            <tr>
                <td>{{ date('d/m/Y', strtotime($row['date'])) }}</td>
                <td class="text-center">{{ $row['transactions'] }} Nota</td>
                <td class="text-right">Rp {{ number_format($row['gross_sales'], 0, ',', '.') }}</td>
                <td class="text-right bold">Rp {{ number_format($row['net_sales'], 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="text-center">Tidak ada data transaksi.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Breakdown Metode Pembayaran & Kasir -->
    <table style="width: 100%;">
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 10px;">
                <h3 style="margin-bottom: 6px; font-size: 12px;">Metode Pembayaran</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Metode</th>
                            <th class="text-center">Nota</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($paymentBreakdown as $pm)
                        <tr>
                            <td class="uppercase bold">{{ $pm['method'] }}</td>
                            <td class="text-center">{{ $pm['transactions'] }}</td>
                            <td class="text-right">Rp {{ number_format($pm['amount'], 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </td>
            <td style="width: 50%; vertical-align: top; padding-left: 10px;">
                <h3 style="margin-bottom: 6px; font-size: 12px;">Performa Kasir</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Kasir</th>
                            <th class="text-center">Nota</th>
                            <th class="text-right">Penjualan Bersih</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cashiers as $c)
                        <tr>
                            <td class="uppercase bold">{{ $c['cashier_name'] }}</td>
                            <td class="text-center">{{ $c['transactions'] }}</td>
                            <td class="text-right">Rp {{ number_format($c['net_sales'], 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <!-- Tanda Tangan -->
    <table class="signatures">
        <tr>
            <td>
                <p>Dibuat Oleh,</p>
                <br><br><br>
                <p class="bold">( Admin / Kasir )</p>
            </td>
            <td>
                <p>Disetujui Oleh,</p>
                <br><br><br>
                <p class="bold">( Supervisor / Owner )</p>
            </td>
        </tr>
    </table>

</body>
</html>

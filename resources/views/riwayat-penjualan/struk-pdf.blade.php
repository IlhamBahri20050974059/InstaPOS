<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk - {{ $transaksi['invoice_number'] ?? 'INV' }}</title>
    <style>
        @page { margin: 5px; }
        body {
            font-family: 'Courier', 'Courier New', monospace;
            font-size: 10px;
            color: #000;
            margin: 0;
            padding: 8px;
            width: 100%;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        .divider {
            border-top: 1px dashed #000;
            margin: 6px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 2px 0;
            vertical-align: top;
        }
    </style>
</head>
<body onload="window.print()">

    <!-- Header Toko -->
    <div class="text-center">
        <div class="bold uppercase" style="font-size: 12px;">TOKO INSTAPOS</div>
        <div>Jl. Raya Utama Toko No. 123</div>
        <div>Telp: 0812-3456-7890</div>
    </div>

    <div class="divider"></div>

    <!-- Informasi Transaksi -->
    <table>
        <tr>
            <td class="text-left">No. Struk:</td>
            <td class="text-right bold">{{ $transaksi['invoice_number'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="text-left">Tanggal:</td>
            <td class="text-right">{{ isset($transaksi['created_at']) ? date('d/m/Y H:i', strtotime($transaksi['created_at'])) : '-' }}</td>
        </tr>
        <tr>
            <td class="text-left">Kasir:</td>
            <td class="text-right uppercase">{{ $transaksi['cashier_name'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="text-left">Pelanggan:</td>
            <td class="text-right uppercase">{{ $transaksi['customer_name'] ?? 'Umum' }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <!-- Daftar Barang -->
    <table>
        @foreach(($transaksi['items'] ?? []) as $item)
            <tr>
                <td colspan="2" class="bold uppercase">{{ $item['product_name'] }}</td>
            </tr>
            <tr>
                <td class="text-left" style="padding-left: 8px;">
                    {{ $item['quantity'] }} x Rp {{ number_format($item['unit_price'], 0, ',', '.') }}
                </td>
                <td class="text-right">
                    Rp {{ number_format($item['subtotal'], 0, ',', '.') }}
                </td>
            </tr>
        @endforeach
    </table>

    <div class="divider"></div>

    <!-- Ringkasan Pembayaran -->
    @php
        $subtotal = $transaksi['subtotal'] ?? 0;
        $discount = $transaksi['discount'] ?? 0;
        $total = $transaksi['total'] ?? 0;
        $bayar = $transaksi['payment']['amount'] ?? 0;
        $kembalian = $bayar - $total;
    @endphp

    <table>
        <tr>
            <td class="text-left">Subtotal:</td>
            <td class="text-right">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
        </tr>
        @if($discount > 0)
        <tr>
            <td class="text-left">Diskon:</td>
            <td class="text-right">- Rp {{ number_format($discount, 0, ',', '.') }}</td>
        </tr>
        @endif
        <tr>
            <td class="text-left bold">Total Akhir:</td>
            <td class="text-right bold">Rp {{ number_format($total, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="text-left">Bayar ({{ strtoupper($transaksi['payment']['method'] ?? 'CASH') }}):</td>
            <td class="text-right">Rp {{ number_format($bayar, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="text-left">Kembalian:</td>
            <td class="text-right">Rp {{ number_format(max(0, $kembalian), 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <!-- Footer Struk -->
    <div class="text-center" style="margin-top: 8px;">
        <p>*** TERIMA KASIH ***<br>Barang yang sudah dibeli<br>tidak dapat ditukar/dikembalikan</p>
    </div>

</body>
</html>

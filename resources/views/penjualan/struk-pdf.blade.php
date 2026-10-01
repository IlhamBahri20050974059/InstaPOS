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
    @php
        $cashier = $transaksi['cashier_name']
            ?? $transaksi['user']['name']
            ?? $transaksi['cashier']['name']
            ?? session('user')['name']
            ?? 'Kasir';

        $customer = $transaksi['customer_name']
            ?? $transaksi['customer']['name']
            ?? 'Umum';

        $tanggal = isset($transaksi['created_at'])
            ? date('d/m/Y H:i', strtotime($transaksi['created_at']))
            : date('d/m/Y H:i');
    @endphp

    <table>
        <tr>
            <td class="text-left">No. Struk:</td>
            <td class="text-right bold">{{ $transaksi['invoice_number'] ?? $transaksi['id'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="text-left">Tanggal:</td>
            <td class="text-right">{{ $tanggal }}</td>
        </tr>
        <tr>
            <td class="text-left">Kasir:</td>
            <td class="text-right uppercase">{{ $cashier }}</td>
        </tr>
        <tr>
            <td class="text-left">Pelanggan:</td>
            <td class="text-right uppercase">{{ $customer }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <!-- Daftar Barang -->
    @php
        $items = $transaksi['items'] ?? $transaksi['sale_details'] ?? $transaksi['details'] ?? [];
    @endphp

    <table>
        @foreach($items as $item)
            @php
                $namaProduk = $item['product_name'] ?? $item['product']['name'] ?? 'Produk';
                $qty = $item['quantity'] ?? $item['qty'] ?? 1;
                $hargaSatuan = $item['unit_price'] ?? $item['price'] ?? $item['product']['price'] ?? 0;
                $subtotalItem = $item['subtotal'] ?? ($qty * $hargaSatuan);
            @endphp
            <tr>
                <td colspan="2" class="bold uppercase">{{ $namaProduk }}</td>
            </tr>
            <tr>
                <td class="text-left" style="padding-left: 8px;">
                    {{ $qty }} x Rp {{ number_format($hargaSatuan, 0, ',', '.') }}
                </td>
                <td class="text-right">
                    Rp {{ number_format($subtotalItem, 0, ',', '.') }}
                </td>
            </tr>
        @endforeach
    </table>

    <div class="divider"></div>

    <!-- Ringkasan Pembayaran -->
    @php
        $subtotal = $transaksi['subtotal'] ?? $transaksi['total_price'] ?? $transaksi['total'] ?? 0;
        $discount = $transaksi['discount'] ?? 0;
        $total = $transaksi['total'] ?? $transaksi['grand_total'] ?? $subtotal;

        // Handling pembayaran
        $payments = $transaksi['payments'] ?? [];
        $paymentObj = $transaksi['payment'] ?? ($payments[0] ?? []);

        $method = $paymentObj['method'] ?? $paymentObj['payment_method'] ?? 'CASH';
        $bayar = $paymentObj['amount'] ?? $total;
        $kembalian = max(0, $bayar - $total);
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
            <td class="text-left">Bayar ({{ strtoupper($method) }}):</td>
            <td class="text-right">Rp {{ number_format($bayar, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="text-left">Kembalian:</td>
            <td class="text-right">Rp {{ number_format($kembalian, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <div class="text-center" style="margin-top: 8px;">
        <p>*** TERIMA KASIH ***<br>Barang yang sudah dibeli<br>tidak dapat ditukar/dikembalikan</p>
    </div>

</body>
</html>

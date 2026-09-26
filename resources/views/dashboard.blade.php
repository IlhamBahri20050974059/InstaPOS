@php
    $userSession = session('user');

    // Ambil role user dari session (sesuaikan nama key 'role' / 'level' dari API kamu)
    $role = is_array($userSession)
        ? ($userSession['role'] ?? $userSession['level'] ?? 'cashier')
        : ($userSession->role ?? $userSession->level ?? 'cashier');

    // Tentukan layout berdasarkan role
    $layout = (in_array(strtolower($role), ['supervisor', 'spv', 'admin']))
        ? 'layouts.supervisor'
        : 'layouts.app';
@endphp
@extends($layout)

@section('title', 'Dashboard Analitik')

@section('content')
<div class="space-y-6" x-data="dashboardData()">

    <!-- ================= TOP CONTROLS: FILTER PERIODE ================= -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Ringkasan Performa Toko</h1>
            <p class="text-xs text-slate-500 mt-0.5">Pantau omzet, jumlah penjualan, dan tren produk terlaris secara akurat.</p>
        </div>

        <!-- Filter Dropdown (Hari ini / Minggu ini / Bulan ini) -->
        <div class="flex items-center space-x-2">
            <label class="text-xs font-semibold text-slate-500">Periode:</label>
            <select x-model="filterPeriode" @change="updateDashboard()" class="bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                <option value="today">Hari Ini</option>
                <option value="week">Minggu Ini</option>
                <option value="month" selected>Bulan Ini (2026)</option>
            </select>
        </div>
    </div>

    <!-- ================= STAT CARDS RINGKASAN ================= -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

        <!-- Card 1: Total Omzet -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Omzet</p>
                <h3 class="text-2xl font-black text-slate-800 mt-1" x-text="formatRupiah(stats.totalOmzet)"></h3>
                <span class="text-[11px] text-emerald-600 font-semibold bg-emerald-50 px-2 py-0.5 rounded-md mt-2 inline-block">↑ +12.5% vs periode lalu</span>
            </div>
            <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>

        <!-- Card 2: Total Transaksi -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Jumlah Transaksi</p>
                <h3 class="text-2xl font-black text-slate-800 mt-1" x-text="stats.totalTransaksi + ' Nota'"></h3>
                <span class="text-[11px] text-emerald-600 font-semibold bg-emerald-50 px-2 py-0.5 rounded-md mt-2 inline-block">↑ +8.2% transaksi</span>
            </div>
            <div class="p-3 bg-blue-50 text-blue-600 rounded-xl">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
        </div>

        <!-- Card 3: Barang Terjual -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Produk Terjual</p>
                <h3 class="text-2xl font-black text-slate-800 mt-1" x-text="stats.totalProdukTerjual + ' Pcs'"></h3>
                <span class="text-[11px] text-emerald-600 font-semibold bg-emerald-50 px-2 py-0.5 rounded-md mt-2 inline-block">↑ +15% item laku</span>
            </div>
            <div class="p-3 bg-indigo-50 text-indigo-600 rounded-xl">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
        </div>

        <!-- Card 4: Rata-Rata Belanja -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Rata-rata Nota</p>
                <h3 class="text-2xl font-black text-slate-800 mt-1" x-text="formatRupiah(stats.rataRataNota)"></h3>
                <span class="text-[11px] text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md mt-2 inline-block">Stabil</span>
            </div>
            <div class="p-3 bg-amber-50 text-amber-600 rounded-xl">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            </div>
        </div>

    </div>

    <!-- ================= AREA GRAFIK (Sesuai Referensi Gambar) ================= -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Grafik Bar: Grafik Penjualan Bulanan (2/3 width) -->
        <div class="lg:col-span-2 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <h3 class="font-bold text-slate-800 text-base">JUMLAH PENJUALAN BULANAN</h3>
                        <p class="text-xs text-slate-500">Menampilkan tren pendapatan omzet penjualan toko pada tahun 2026.</p>
                    </div>
                </div>
                <div class="flex items-baseline space-x-4 my-3">
                    <span class="text-2xl font-black text-slate-800" x-text="formatRupiah(stats.totalOmzet)"></span>
                    <span class="text-xs text-emerald-600 font-semibold">● Total Omzet Terakumulasi</span>
                </div>
            </div>

            <!-- Canvas Chart.js Bar -->
            <div class="relative h-64 w-full mt-2">
                <canvas id="barChartPenjualan"></canvas>
            </div>
        </div>

        <!-- Grafik Pie: Produk Terlaris (1/3 width) -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between">
            <div>
                <h3 class="font-bold text-slate-800 text-base">PRODUK TERLARIS</h3>
                <p class="text-xs text-slate-500 mb-4">Berdasarkan kategori produk per tahun 2026.</p>
            </div>

            <!-- Canvas Chart.js Pie/Donut -->
            <div class="relative h-56 w-full flex items-center justify-center">
                <canvas id="pieChartTerlaris"></canvas>
            </div>

            <div class="mt-4 pt-4 border-t border-slate-100 text-center">
                <p class="text-xs text-slate-400">Kategori Sembako & Minuman mendominasi 70% penjualan.</p>
            </div>
        </div>

    </div>

    <!-- ================= TABEL RIWAYAT PEMESANAN TERBARU (Sesuai Referensi Gambar) ================= -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center">
            <div>
                <h3 class="font-bold text-slate-800 text-base uppercase">Riwayat Pemesanan Terbaru</h3>
                <p class="text-xs text-slate-500 mt-0.5">Menampilkan 5 riwayat transaksi terakhir di kasir.</p>
            </div>
            <a href="{{ url('/riwayat') }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-800">
                Lihat Semua →
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-4">Tanggal</th>
                        <th class="py-3 px-4">No. Struk</th>
                        <th class="py-3 px-4">Kasir / Pelanggan</th>
                        <th class="py-3 px-4">Ringkasan Produk</th>
                        <th class="py-3 px-4 text-right">Total Bayar</th>
                        <th class="py-3 px-4 text-center">Metode</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                    <template x-for="item in riwayatTerbaru" :key="item.id">
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3.5 px-4 font-mono text-slate-500" x-text="item.tanggal"></td>
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-800" x-text="item.noStruk"></td>
                            <td class="py-3.5 px-4 font-semibold text-slate-800" x-text="item.pelanggan"></td>
                            <td class="py-3.5 px-4 text-slate-600" x-text="item.produk"></td>
                            <td class="py-3.5 px-4 text-right font-bold text-emerald-700" x-text="formatRupiah(item.total)"></td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="px-2 py-1 rounded-md text-[10px] font-bold uppercase"
                                      :class="{
                                        'bg-emerald-100 text-emerald-700': item.metode === 'Tunai',
                                        'bg-blue-100 text-blue-700': item.metode === 'QRIS',
                                        'bg-purple-100 text-purple-700': item.metode === 'Debit'
                                      }"
                                      x-text="item.metode">
                                </span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function dashboardData() {
        return {
            filterPeriode: 'month',
            stats: {
                totalOmzet: 841891064,
                totalTransaksi: 1245,
                totalProdukTerjual: 3890,
                rataRataNota: 676200
            },
            riwayatTerbaru: [
                { id: 1, tanggal: '2026-09-25 14:20', noStruk: 'STR-20260925-001', pelanggan: 'Umum (Walk-in)', produk: 'Indomie Goreng (x5), Aqua 600ml (x2)', total: 22500, metode: 'Tunai' },
                { id: 2, tanggal: '2026-09-28 15:05', noStruk: 'STR-20260928-012', pelanggan: 'PT. Lamtana Multijaya', produk: 'Minyak Bimoli 2L (x10), Beras 5kg (x2)', total: 533000, metode: 'QRIS' },
                { id: 3, tanggal: '2026-10-07 10:11', noStruk: 'STR-20261007-005', pelanggan: 'R.S. Bhayangkara', produk: 'Tisu Paseo (x20), Deterjen Rinso (x5)', total: 390000, metode: 'Debit' },
                { id: 4, tanggal: '2026-10-12 18:45', noStruk: 'STR-20261012-044', pelanggan: 'Dwi Nur Fitasari', produk: 'Silverqueen 58g (x3), Teh Pucuk (x4)', total: 65500, metode: 'Tunai' },
                { id: 5, tanggal: '2026-10-20 09:30', noStruk: 'STR-20261020-002', pelanggan: 'Kak Aris', produk: 'Susu Ultra Milk 250ml (x12)', total: 90000, metode: 'QRIS' }
            ],

            formatRupiah(number) {
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(number);
            },

            // Alpine.js akan otomatis menjalankan fungsi init() ini ketika komponen siap
            init() {
                this.$nextTick(() => {
                    this.renderCharts();
                });
            },

            renderCharts() {
                // 1. Grafik Bar Penjualan
                const ctxBar = document.getElementById('barChartPenjualan');
                if (ctxBar) {
                    new Chart(ctxBar, {
                        type: 'bar',
                        data: {
                            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                            datasets: [{
                                label: 'Omzet Penjualan (Rp)',
                                data: [82000000, 105000000, 88000000, 55000000, 92000000, 108000000, 64000000, 180000000, 50000000, 0, 0, 0],
                                backgroundColor: '#059669',
                                borderRadius: 6
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                y: {
                                    ticks: {
                                        callback: function(value) { return 'Rp ' + (value/1000000) + 'Jt'; }
                                    }
                                }
                            }
                        }
                    });
                }

                // 2. Grafik Pie / Donut Terlaris
                const ctxPie = document.getElementById('pieChartTerlaris');
                if (ctxPie) {
                    new Chart(ctxPie, {
                        type: 'doughnut',
                        data: {
                            labels: ['Sembako', 'Minuman', 'Snack', 'Perawatan Diri', 'Kebersihan'],
                            datasets: [{
                                data: [1244, 981, 651, 114, 125],
                                backgroundColor: ['#f59e0b', '#3b82f6', '#ef4444', '#a855f7', '#10b981']
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } }
                            }
                        }
                    });
                }
            },

            updateDashboard() {
                alert('Filter periode diubah menjadi: ' + this.filterPeriode);
            }
        }
    }
</script>
@endpush

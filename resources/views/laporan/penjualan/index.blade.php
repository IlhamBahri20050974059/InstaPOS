@php
    $userSession = session('user');
    $role = is_array($userSession)
        ? ($userSession['role'] ?? $userSession['level'] ?? 'cashier')
        : ($userSession->role ?? $userSession->level ?? 'cashier');

    $layout = (in_array(strtolower($role), ['supervisor', 'spv', 'admin']))
        ? 'layouts.supervisor'
        : 'layouts.app';
@endphp
@extends($layout)

@section('title', 'Laporan Penjualan')

@section('content')
<div x-data="laporanPenjualanManager()" x-init="init()" class="space-y-6 pb-12">

    <!-- HEADER & ACTION BUTTONS -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Laporan Penjualan</h1>
            <p class="text-xs text-slate-500 mt-0.5">Analisis ringkasan transaksi, penerimaan, dan tren penjualan</p>
        </div>

        <div class="flex items-center gap-2">
            <!-- Tombol Export PDF -->
            <a :href="getExportUrl('pdf')" target="_blank"
               class="px-3.5 py-2 bg-red-50 hover:bg-red-100 text-red-700 font-bold text-xs rounded-xl border border-red-200/80 transition flex items-center gap-1.5 shadow-sm">
                <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9h1m0 4h6m-6 4h6"/></svg>
                <span>Cetak PDF</span>
            </a>

            <!-- Tombol Export CSV / Excel -->
            <a :href="getExportUrl('csv')"
               class="px-3.5 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold text-xs rounded-xl border border-emerald-200/80 transition flex items-center gap-1.5 shadow-sm">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Ekspor CSV</span>
            </a>
        </div>
    </div>

    <!-- CARD FILTER TANGGAL (PRESET & CUSTOM RANGE) -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Quick Select (Preset Periode)</span>

            <!-- Preset Buttons -->
            <div class="flex flex-wrap items-center gap-1.5">
                <button @click="setPreset('today')" :class="activePreset === 'today' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-3 py-1.5 rounded-lg text-xs font-bold transition">Hari Ini</button>
                <button @click="setPreset('yesterday')" :class="activePreset === 'yesterday' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-3 py-1.5 rounded-lg text-xs font-bold transition">Kemarin</button>
                <button @click="setPreset('last7')" :class="activePreset === 'last7' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-3 py-1.5 rounded-lg text-xs font-bold transition">7 Hari Terakhir</button>
                <button @click="setPreset('thisMonth')" :class="activePreset === 'thisMonth' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-3 py-1.5 rounded-lg text-xs font-bold transition">Bulan Ini</button>
                <button @click="setPreset('lastMonth')" :class="activePreset === 'lastMonth' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-3 py-1.5 rounded-lg text-xs font-bold transition">Bulan Lalu</button>
            </div>
        </div>

        <!-- Custom Date Range Form -->
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Tanggal Mulai (From)</label>
                    <input type="date" x-model="filterFrom" @change="activePreset = 'custom'" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Tanggal Selesai (To)</label>
                    <input type="date" x-model="filterTo" @change="activePreset = 'custom'" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <div class="sm:self-end">
                <button @click="loadReport()" class="w-full sm:w-auto px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl transition flex items-center justify-center gap-2 shadow-sm">
                    <svg class="w-4 h-4" :class="isLoading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span>Tampilkan Laporan</span>
                </button>
            </div>
        </div>
    </div>

    <!-- PREVIEW RINGKASAN (SUMMARY CARDS) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Penjualan Bersih -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Penjualan Bersih (Net)</span>
                <h3 class="text-xl font-extrabold text-emerald-600 mt-1" x-text="formatRupiah(summary.net_sales)"></h3>
                <span class="text-[10px] text-slate-400" x-text="'Gross: ' + formatRupiah(summary.gross_sales)"></span>
            </div>
            <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>

        <!-- Total Diskon -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Diskon</span>
                <h3 class="text-xl font-extrabold text-red-500 mt-1" x-text="formatRupiah(summary.total_discounts)"></h3>
                <span class="text-[10px] text-slate-400">Potongan Harga</span>
            </div>
            <div class="w-12 h-12 bg-red-50 text-red-500 rounded-2xl flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 002 2h14a2 2 0 002-2V7a2 2 0 00-2-2H5z"/></svg>
            </div>
        </div>

        <!-- Total Transaksi -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Nota Transaksi</span>
                <h3 class="text-xl font-extrabold text-slate-800 mt-1" x-text="(summary.transaction_count || 0) + ' Transaksi'"></h3>
                <span class="text-[10px] text-slate-400" x-text="'Rata-rata: ' + formatRupiah(summary.average_transaction_value)"></span>
            </div>
            <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
        </div>

        <!-- Items Sold -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Produk Terjual</span>
                <h3 class="text-xl font-extrabold text-slate-800 mt-1" x-text="(summary.items_sold || 0) + ' Pcs'"></h3>
                <span class="text-[10px] text-slate-400">Total Akumulasi Qty</span>
            </div>
            <div class="w-12 h-12 bg-purple-50 text-purple-600 rounded-2xl flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
        </div>
    </div>

    <!-- GRID BREAKDOWN LAPORAN -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- TABEL TREN PENJUALAN HARIAN (8 COL) -->
        <div class="lg:col-span-8 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
            <h3 class="text-sm font-bold text-slate-800">Rincian Tren Penjualan Harian</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase text-[10px]">
                            <th class="pb-2.5">Tanggal</th>
                            <th class="pb-2.5 text-center">Transaksi</th>
                            <th class="pb-2.5 text-right">Penjualan Kotor</th>
                            <th class="pb-2.5 text-right">Penjualan Bersih</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="row in dailyTrend" :key="row.date">
                            <tr class="hover:bg-slate-50">
                                <td class="py-2.5 font-bold text-slate-700" x-text="formatTanggal(row.date)"></td>
                                <td class="py-2.5 text-center font-bold text-slate-600" x-text="row.transactions + ' nota'"></td>
                                <td class="py-2.5 text-right text-slate-500" x-text="formatRupiah(row.gross_sales)"></td>
                                <td class="py-2.5 text-right font-extrabold text-emerald-600" x-text="formatRupiah(row.net_sales)"></td>
                            </tr>
                        </template>
                        <template x-if="dailyTrend.length === 0">
                            <tr>
                                <td colspan="4" class="py-6 text-center text-slate-400 italic">Tidak ada data transaksi pada periode ini.</td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- BREAKDOWN PEMBAYARAN & KASIR (4 COL) -->
        <div class="lg:col-span-4 space-y-6">

            <!-- Payment Method Breakdown -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-3">
                <h3 class="text-sm font-bold text-slate-800">Metode Pembayaran</h3>
                <div class="space-y-2">
                    <template x-for="pm in paymentBreakdown" :key="pm.method">
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 flex items-center justify-between">
                            <div>
                                <h4 class="text-xs font-bold text-slate-800 uppercase" x-text="pm.method"></h4>
                                <span class="text-[10px] text-slate-400" x-text="pm.transactions + ' transaksi (' + pm.percentage + '%)'"></span>
                            </div>
                            <span class="text-xs font-extrabold text-slate-800" x-text="formatRupiah(pm.amount)"></span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Cashiers Breakdown -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-3">
                <h3 class="text-sm font-bold text-slate-800">Performa Kasir</h3>
                <div class="space-y-2">
                    <template x-for="c in cashiers" :key="c.cashier_id">
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 flex items-center justify-between">
                            <div>
                                <h4 class="text-xs font-bold text-slate-800 uppercase" x-text="c.cashier_name"></h4>
                                <span class="text-[10px] text-slate-400" x-text="c.transactions + ' transaksi melayani'"></span>
                            </div>
                            <span class="text-xs font-extrabold text-emerald-600" x-text="formatRupiah(c.net_sales)"></span>
                        </div>
                    </template>
                </div>
            </div>

        </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
    function laporanPenjualanManager() {
        return {
            filterFrom: '',
            filterTo: '',
            activePreset: 'thisMonth',
            isLoading: false,
            summary: {},
            dailyTrend: [],
            paymentBreakdown: [],
            cashiers: [],

            init() {
                this.setPreset('thisMonth');
            },

            setPreset(type) {
                this.activePreset = type;
                const now = new Date();

                if (type === 'today') {
                    this.filterFrom = this.formatYmd(now);
                    this.filterTo = this.formatYmd(now);
                } else if (type === 'yesterday') {
                    const yest = new Date(now);
                    yest.setDate(yest.getDate() - 1);
                    this.filterFrom = this.formatYmd(yest);
                    this.filterTo = this.formatYmd(yest);
                } else if (type === 'last7') {
                    const last7 = new Date(now);
                    last7.setDate(last7.getDate() - 6);
                    this.filterFrom = this.formatYmd(last7);
                    this.filterTo = this.formatYmd(now);
                } else if (type === 'thisMonth') {
                    const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
                    this.filterFrom = this.formatYmd(firstDay);
                    this.filterTo = this.formatYmd(now);
                } else if (type === 'lastMonth') {
                    const firstDayLastMonth = new Date(now.getFullYear(), now.getMonth() - 1, 1);
                    const lastDayLastMonth = new Date(now.getFullYear(), now.getMonth(), 0);
                    this.filterFrom = this.formatYmd(firstDayLastMonth);
                    this.filterTo = this.formatYmd(lastDayLastMonth);
                }

                this.loadReport();
            },

            async loadReport() {
                this.isLoading = true;

                // Parameter 'to' ditambah 1 hari untuk menyesuaikan sifat exclusive API
                let adjustedTo = new Date(this.filterTo);
                adjustedTo.setDate(adjustedTo.getDate() + 1);
                const toQuery = this.formatYmd(adjustedTo);

                try {
                    const res = await fetch(`{{ route('laporan.penjualan.data') }}?from=${this.filterFrom}&to=${toQuery}`);
                    const json = await res.json();

                    if (res.ok && json.data) {
                        const d = json.data;
                        this.summary = d.summary || {};
                        this.dailyTrend = d.daily_trend || [];
                        this.paymentBreakdown = d.payment_breakdown || [];
                        this.cashiers = d.cashiers || [];
                    }
                } catch (e) {
                    console.error('Gagal memuat laporan penjualan:', e);
                } finally {
                    this.isLoading = false;
                }
            },

            getExportUrl(type) {
                let adjustedTo = new Date(this.filterTo);
                adjustedTo.setDate(adjustedTo.getDate() + 1);
                const toQuery = this.formatYmd(adjustedTo);

                const baseUrl = type === 'pdf'
                    ? `{{ route('laporan.penjualan.export-pdf') }}`
                    : `{{ route('laporan.penjualan.export-csv') }}`;

                return `${baseUrl}?from=${this.filterFrom}&to=${toQuery}`;
            },

            formatYmd(dateObj) {
                const year = dateObj.getFullYear();
                const month = String(dateObj.getMonth() + 1).padStart(2, '0');
                const day = String(dateObj.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            },

            formatRupiah(val) {
                if (!val && val !== 0) return 'Rp 0';
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val);
            },

            formatTanggal(dateStr) {
                if (!dateStr) return '-';
                const d = new Date(dateStr);
                return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
            }
        }
    }
</script>
@endpush

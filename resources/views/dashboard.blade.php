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

@section('title', 'Dashboard Ringkasan')

@push('styles')
<!-- CDN Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@section('content')
<div x-data="dashboardManager()" x-init="init()" class="space-y-6 pb-10">

    <!-- Header & Date Range Filter -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Dashboard Performa</h1>
            <p class="text-xs text-slate-500 mt-0.5">Ringkasan statistik dan tren penjualan InstaPOS</p>
        </div>

        <div class="flex items-center gap-2 bg-slate-50 p-1.5 rounded-xl border border-slate-200">
            <input type="date" x-model="filterFrom" class="px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
            <span class="text-xs text-slate-400 font-bold">s/d</span>
            <input type="date" x-model="filterTo" class="px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
            
            <button @click="loadData()" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-lg transition flex items-center gap-1">
                <svg class="w-3.5 h-3.5" :class="isLoading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <span>Filter</span>
            </button>
        </div>
    </div>

    <!-- 4 SUMMARY CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Revenue -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Omset</span>
                <h3 class="text-xl font-extrabold text-slate-800 mt-1" x-text="formatRupiah(summary.revenue)"></h3>
            </div>
            <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>

        <!-- Transactions -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Transaksi</span>
                <h3 class="text-xl font-extrabold text-slate-800 mt-1" x-text="(summary.transaction_count || 0) + ' Transaksi'"></h3>
            </div>
            <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            </div>
        </div>

        <!-- Items Sold -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Produk Terjual</span>
                <h3 class="text-xl font-extrabold text-slate-800 mt-1" x-text="(summary.items_sold || 0) + ' Pcs'"></h3>
            </div>
            <div class="w-12 h-12 bg-purple-50 text-purple-600 rounded-2xl flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
        </div>

        <!-- Average Order Value -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Rata-rata / Nota</span>
                <h3 class="text-xl font-extrabold text-slate-800 mt-1" x-text="formatRupiah(summary.average_transaction_value)"></h3>
            </div>
            <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </div>
        </div>
    </div>

    <!-- GRAFIK TREN PENJUALAN & METODE PEMBAYARAN -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Sales Trend Line Chart (8 COL) -->
        <div class="lg:col-span-8 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
            <h2 class="text-sm font-bold text-slate-800">Grafik Tren Penjualan Harian</h2>
            <div class="relative h-64">
                <canvas id="salesTrendChart"></canvas>
            </div>
        </div>

        <!-- Payment Methods Doughnut Chart (4 COL) -->
        <div class="lg:col-span-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-4 flex flex-col justify-between">
            <h2 class="text-sm font-bold text-slate-800">Metode Pembayaran</h2>
            <div class="relative h-48 my-auto">
                <canvas id="paymentMethodChart"></canvas>
            </div>
            <div class="space-y-1.5 pt-3 border-t border-slate-100">
                <template x-for="pm in paymentMethods" :key="pm.method">
                    <div class="flex justify-between items-center text-xs">
                        <span class="font-bold text-slate-600 uppercase" x-text="pm.method"></span>
                        <span class="font-extrabold text-slate-800" x-text="formatRupiah(pm.revenue)"></span>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- PRODUK TERLARIS & TRANSAKSI TERAKHIR -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Top Products List (5 COL) -->
        <div class="lg:col-span-5 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
            <h2 class="text-sm font-bold text-slate-800">Top 5 Produk Terlaris</h2>
            <div class="space-y-3">
                <template x-for="(tp, idx) in topProducts" :key="tp.product_id">
                    <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <div class="flex items-center gap-3">
                            <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 font-black text-xs flex items-center justify-center" x-text="idx + 1"></span>
                            <div>
                                <h4 class="text-xs font-bold text-slate-800 uppercase" x-text="tp.product_name"></h4>
                                <span class="text-[10px] font-semibold text-slate-400" x-text="tp.quantity + ' pcs terjual'"></span>
                            </div>
                        </div>
                        <span class="text-xs font-extrabold text-emerald-600" x-text="formatRupiah(tp.revenue)"></span>
                    </div>
                </template>
            </div>
        </div>

        <!-- Recent Sales Table (7 COL) -->
        <div class="lg:col-span-7 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
            <div class="flex justify-between items-center">
                <h2 class="text-sm font-bold text-slate-800">Transaksi Terbaru</h2>
                <a href="{{ route('riwayat-penjualan.index') }}" class="text-xs font-bold text-emerald-600 hover:underline">Lihat Semua</a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase text-[10px]">
                            <th class="pb-2">Invoice</th>
                            <th class="pb-2">Kasir</th>
                            <th class="pb-2">Total</th>
                            <th class="pb-2">Metode</th>
                            <th class="pb-2 text-right">Waktu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="sale in recentSales" :key="sale.id">
                            <tr class="hover:bg-slate-50/80">
                                <td class="py-2.5 font-bold text-slate-800" x-text="sale.invoice_number"></td>
                                <td class="py-2.5 text-slate-600 uppercase" x-text="sale.cashier_name"></td>
                                <td class="py-2.5 font-extrabold text-emerald-600" x-text="formatRupiah(sale.total)"></td>
                                <td class="py-2.5">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase bg-slate-100 text-slate-700"
                                          x-text="sale.payments?.[0]?.method || 'cash'"></span>
                                </td>
                                <td class="py-2.5 text-right text-slate-400 text-[10px]" x-text="formatTanggal(sale.created_at)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function dashboardManager() {
        return {
            filterFrom: '',
            filterTo: '',
            isLoading: false,
            summary: {},
            salesTrend: [],
            recentSales: [],
            topProducts: [],
            paymentMethods: [],
            trendChart: null,
            paymentChart: null,

            init() {
                // Set default tanggal dari bulan ini
                const now = new Date();
                const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
                
                this.filterFrom = firstDay.toISOString().split('T')[0];
                this.filterTo = now.toISOString().split('T')[0];

                this.loadData();
            },

            async loadData() {
                this.isLoading = true;

                // Parameter 'to' ditambah 1 hari untuk menyesuaikan sifat exclusive API
                let adjustedTo = new Date(this.filterTo);
                adjustedTo.setDate(adjustedTo.getDate() + 1);
                const toQuery = adjustedTo.toISOString().split('T')[0];

                try {
                    const res = await fetch(`{{ route('dashboard.data') }}?from=${this.filterFrom}&to=${toQuery}`);
                    const json = await res.json();

                    if (res.ok && json.data) {
                        const d = json.data;
                        this.summary = d.summary || {};
                        this.salesTrend = d.sales_trend || [];
                        this.recentSales = d.recent_sales || [];
                        this.topProducts = d.top_products || [];
                        this.paymentMethods = d.payment_methods || [];

                        this.renderTrendChart();
                        this.renderPaymentChart();
                    }
                } catch (e) {
                    console.error('Gagal memuat data dashboard:', e);
                } finally {
                    this.isLoading = false;
                }
            },

            renderTrendChart() {
                const ctx = document.getElementById('salesTrendChart').getContext('2d');
                if (this.trendChart) this.trendChart.destroy();

                const labels = this.salesTrend.map(i => i.date);
                const revenues = this.salesTrend.map(i => i.revenue);

                this.trendChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Omset (Rp)',
                            data: revenues,
                            borderColor: '#059669',
                            backgroundColor: 'rgba(5, 150, 105, 0.1)',
                            fill: true,
                            tension: 0.3,
                            borderWidth: 2,
                            pointRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: {
                                ticks: {
                                    callback: value => 'Rp ' + value.toLocaleString('id-ID')
                                }
                            }
                        }
                    }
                });
            },

            renderPaymentChart() {
                const ctx = document.getElementById('paymentMethodChart').getContext('2d');
                if (this.paymentChart) this.paymentChart.destroy();

                const labels = this.paymentMethods.map(i => i.method.toUpperCase());
                const revenues = this.paymentMethods.map(i => i.revenue);

                this.paymentChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: revenues,
                            backgroundColor: ['#059669', '#2563eb', '#9333ea', '#f59e0b'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom' } }
                    }
                });
            },

            formatRupiah(val) {
                if (!val && val !== 0) return 'Rp 0';
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val);
            },

            formatTanggal(dateStr) {
                if (!dateStr) return '-';
                const d = new Date(dateStr);
                return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
            }
        }
    }
</script>
@endpush

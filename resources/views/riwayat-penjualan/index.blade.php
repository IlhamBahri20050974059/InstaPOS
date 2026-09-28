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

@section('title', 'Riwayat Penjualan')

@section('content')
<div x-data="riwayatManager()" x-init="init()" class="space-y-5">

    <!-- Header Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
        <div>
            <h1 class="text-lg font-bold text-slate-800">Riwayat Penjualan</h1>
            <p class="text-xs text-slate-500 mt-0.5">Daftar transaksi penjualan toko dan cetak ulang struk belanja.</p>
        </div>

        <div class="flex items-center gap-2">
            <button @click="loadData()"
                    class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition flex items-center gap-2 text-xs font-semibold">
                <svg class="w-4 h-4" :class="isLoading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span>Refresh Data</span>
            </button>
        </div>
    </div>

    <!-- Stat Ringkasan -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-[11px] font-semibold text-slate-400">Total Transaksi</p>
                <h3 class="text-lg font-extrabold text-slate-800" x-text="filteredSales().length">0</h3>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-[11px] font-semibold text-slate-400">Total Omset</p>
                <h3 class="text-lg font-extrabold text-slate-800" x-text="formatRupiah(hitungTotalOmset())">Rp 0</h3>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </div>
            <div>
                <p class="text-[11px] font-semibold text-slate-400">Kasir Aktif</p>
                <h3 class="text-lg font-extrabold text-slate-800 uppercase">{{ $userSession['name'] ?? 'Kasir' }}</h3>
            </div>
        </div>
    </div>

    <!-- Toolbar Filter -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row gap-3 items-center justify-between">
        <div class="relative w-full md:w-80">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </span>
            <input type="text"
                   x-model="searchQuery"
                   placeholder="Cari No. Struk / Nama Pelanggan..."
                   class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:bg-white transition">
        </div>

        <div class="flex items-center gap-2 w-full md:w-auto">
            <input type="date"
                   x-model="filterDate"
                   class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
            <button @click="filterDate = ''"
                    x-show="filterDate"
                    class="text-xs text-rose-500 font-semibold hover:underline">Reset Tanggal</button>
        </div>
    </div>

    <!-- Tabel Riwayat Penjualan -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <template x-if="isLoading">
            <div class="p-12 flex flex-col items-center justify-center text-slate-400 space-y-3">
                <svg class="w-8 h-8 animate-spin text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <p class="text-xs font-semibold">Memuat riwayat transaksi...</p>
            </div>
        </template>

        <template x-if="!isLoading">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            <th class="py-3.5 px-4">No. Struk</th>
                            <th class="py-3.5 px-4">Waktu</th>
                            <th class="py-3.5 px-4">Kasir</th>
                            <th class="py-3.5 px-4">Pelanggan</th>
                            <th class="py-3.5 px-4 text-center">Metode</th>
                            <th class="py-3.5 px-4 text-right">Total Transaksi</th>
                            <th class="py-3.5 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">

                        <template x-if="filteredSales().length === 0">
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    <svg class="w-10 h-10 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <p class="font-semibold text-slate-500">Tidak ada riwayat penjualan ditemukan</p>
                                </td>
                            </tr>
                        </template>

                        <template x-for="sale in filteredSales()" :key="sale.id">
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-4 font-mono font-bold text-emerald-600" x-text="sale.invoice_number"></td>
                                <td class="py-3.5 px-4 text-slate-600" x-text="formatTanggal(sale.created_at)"></td>
                                <td class="py-3.5 px-4 font-semibold text-slate-700 uppercase" x-text="sale.cashier_name || '-'"></td>
                                <td class="py-3.5 px-4 text-slate-600 capitalize" x-text="sale.customer_name || 'Umum'"></td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="px-2.5 py-1 bg-slate-100 text-slate-700 rounded-lg text-[10px] font-bold uppercase"
                                          x-text="sale.payment?.method || 'cash'">
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right font-bold text-slate-800" x-text="formatRupiah(sale.total)"></td>
                                <td class="py-3.5 px-4 text-center space-x-1">
                                    <button @click="openDetailModal(sale)"
                                            class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition font-semibold text-xs inline-flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        <span>Detail</span>
                                    </button>

                                    <a :href="'/riwayat-penjualan/' + sale.id + '/cetak-pdf'" target="_blank"
                                       class="px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-lg transition font-semibold text-xs inline-flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                        <span>Cetak PDF</span>
                                    </a>
                                </td>
                            </tr>
                        </template>

                    </tbody>
                </table>
            </div>
        </template>
    </div>

    <!-- ================= MODAL DETAIL TRANSAKSI ================= -->
    <div x-show="showDetailModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
        <div class="bg-white w-full max-w-lg rounded-2xl shadow-xl overflow-hidden" @click.away="showDetailModal = false">
            <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <div>
                    <h3 class="font-bold text-slate-800 text-sm">Detail Transaksi Penjualan</h3>
                    <p class="text-[11px] font-mono text-emerald-600 font-bold" x-text="selectedSale?.invoice_number"></p>
                </div>
                <button @click="showDetailModal = false" class="p-1 text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="p-5 space-y-4 max-h-[80vh] overflow-y-auto">
                <!-- Info Ringkas -->
                <div class="grid grid-cols-2 gap-3 text-xs bg-slate-50 p-3 rounded-xl border border-slate-100">
                    <div>
                        <span class="text-slate-400 block text-[10px]">Waktu Transaksi</span>
                        <span class="font-semibold text-slate-700" x-text="formatTanggal(selectedSale?.created_at)"></span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px]">Kasir</span>
                        <span class="font-bold text-slate-700 uppercase" x-text="selectedSale?.cashier_name || '-'"></span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px]">Pelanggan</span>
                        <span class="font-semibold text-slate-700 capitalize" x-text="selectedSale?.customer_name || 'Umum'"></span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px]">Metode Pembayaran</span>
                        <span class="font-bold text-slate-700 uppercase" x-text="selectedSale?.payment?.method || 'cash'"></span>
                    </div>
                </div>

                <!-- Tabel Item -->
                <div class="border border-slate-100 rounded-xl overflow-hidden">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 font-bold text-[10px] uppercase">
                            <tr>
                                <th class="p-2.5">Item Barang</th>
                                <th class="p-2.5 text-center">Qty</th>
                                <th class="p-2.5 text-right">Harga</th>
                                <th class="p-2.5 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="item in (selectedSale?.items || [])" :key="item.product_name">
                                <tr>
                                    <td class="p-2.5 font-bold text-slate-800 uppercase" x-text="item.product_name"></td>
                                    <td class="p-2.5 text-center text-slate-600" x-text="item.quantity"></td>
                                    <td class="p-2.5 text-right text-slate-600" x-text="formatRupiah(item.unit_price)"></td>
                                    <td class="p-2.5 text-right font-bold text-slate-800" x-text="formatRupiah(item.subtotal)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Total & Pembayaran -->
                <div class="space-y-1.5 text-xs pt-2 border-t border-slate-100">
                    <div class="flex justify-between text-slate-500">
                        <span>Subtotal</span>
                        <span class="font-semibold" x-text="formatRupiah(selectedSale?.subtotal)"></span>
                    </div>
                    <div class="flex justify-between text-slate-500" x-show="selectedSale?.discount > 0">
                        <span>Diskon</span>
                        <span class="font-semibold text-rose-500" x-text="'- ' + formatRupiah(selectedSale?.discount)"></span>
                    </div>
                    <div class="flex justify-between text-slate-800 font-extrabold text-sm pt-1">
                        <span>Total Bayar</span>
                        <span class="text-emerald-600" x-text="formatRupiah(selectedSale?.total)"></span>
                    </div>
                    <div class="flex justify-between text-slate-600 pt-1">
                        <span>Diterima (<span class="uppercase" x-text="selectedSale?.payment?.method"></span>)</span>
                        <span class="font-semibold" x-text="formatRupiah(selectedSale?.payment?.amount)"></span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Kembalian</span>
                        <span class="font-bold text-slate-800" x-text="formatRupiah(hitungKembalian(selectedSale))"></span>
                    </div>
                </div>
            </div>

            <div class="p-4 border-t border-slate-100 flex justify-end gap-2 bg-slate-50/50">
                <button type="button" @click="showDetailModal = false" class="px-4 py-2 bg-slate-100 text-slate-600 font-semibold text-xs rounded-xl hover:bg-slate-200">
                    Tutup
                </button>
                <a :href="'/riwayat-penjualan/' + selectedSale?.id + '/cetak-pdf'" target="_blank"
                   class="px-4 py-2 bg-emerald-600 text-white font-bold text-xs rounded-xl hover:bg-emerald-700 flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    <span>Cetak Struk PDF</span>
                </a>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function riwayatManager() {
        return {
            sales: [],
            searchQuery: '',
            filterDate: '',
            isLoading: false,
            showDetailModal: false,
            selectedSale: null,

            async init() {
                await this.loadData();
            },

            async loadData() {
                this.isLoading = true;
                try {
                    const res = await fetch("{{ route('riwayat-penjualan.data') }}");
                    const json = await res.json();
                    this.sales = json.data || json || [];
                } catch (e) {
                    alert('Gagal memuat data riwayat penjualan');
                } finally {
                    this.isLoading = false;
                }
            },

            filteredSales() {
                return this.sales.filter(s => {
                    const query = this.searchQuery.toLowerCase();
                    const matchInvoice = s.invoice_number ? s.invoice_number.toLowerCase().includes(query) : false;
                    const matchCustomer = s.customer_name ? s.customer_name.toLowerCase().includes(query) : false;
                    const matchSearch = !this.searchQuery || matchInvoice || matchCustomer;

                    const matchDate = !this.filterDate || (s.created_at && s.created_at.startsWith(this.filterDate));

                    return matchSearch && matchDate;
                });
            },

            hitungTotalOmset() {
                return this.filteredSales().reduce((acc, curr) => acc + (curr.total || 0), 0);
            },

            hitungKembalian(sale) {
                if (!sale || !sale.payment) return 0;
                const bayar = sale.payment.amount || 0;
                const total = sale.total || 0;
                return Math.max(0, bayar - total);
            },

            formatRupiah(number) {
                if (!number && number !== 0) return 'Rp 0';
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(number);
            },

            formatTanggal(dateString) {
                if (!dateString) return '-';
                const date = new Date(dateString);
                return date.toLocaleDateString('id-ID', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            },

            openDetailModal(sale) {
                this.selectedSale = sale;
                this.showDetailModal = true;
            }
        }
    }
</script>
@endpush

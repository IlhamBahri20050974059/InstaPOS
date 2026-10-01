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

@section('title', 'Transaksi Penjualan (POS)')

@section('content')
<div x-data="posManager()" x-init="init()" class="grid grid-cols-1 lg:grid-cols-12 gap-5 h-[calc(100vh-6rem)]">

    <!-- KOLOM KIRI: KATALOG PRODUK (7 COL) -->
    <div class="lg:col-span-7 flex flex-col space-y-4 h-full overflow-hidden">

        <!-- Search & Filter Bar -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-3">
            <div class="relative flex-1">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </span>
                <input type="text"
                       x-model="searchProduct"
                       x-ref="searchInput"
                       placeholder="Cari nama produk / scan barcode..."
                       class="w-full pl-9 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:bg-white transition">
            </div>
            <button @click="loadProducts()" class="p-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl transition" title="Refresh Produk">
                <svg class="w-4 h-4" :class="isLoadingProducts ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            </button>
        </div>

        <!-- Grid Produk -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex-1 overflow-y-auto">
            <template x-if="isLoadingProducts">
                <div class="h-full flex flex-col items-center justify-center text-slate-400 space-y-2">
                    <svg class="w-8 h-8 animate-spin text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <p class="text-xs font-semibold">Memuat katalog produk...</p>
                </div>
            </template>

            <template x-if="!isLoadingProducts && filteredProducts().length === 0">
                <div class="h-full flex flex-col items-center justify-center text-slate-400">
                    <svg class="w-10 h-10 mb-2 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                    <p class="text-xs font-semibold">Produk tidak ditemukan</p>
                </div>
            </template>

            <div x-show="!isLoadingProducts" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 gap-3">
                <template x-for="p in filteredProducts()" :key="p.id">
                    <div @click="addToCart(p)"
                         class="bg-slate-50 hover:bg-emerald-50/50 border border-slate-200/80 hover:border-emerald-500 rounded-xl p-3 cursor-pointer transition flex flex-col justify-between space-y-2 group">
                        <div>
                            <div class="flex justify-between items-start gap-1">
                                <span class="text-[10px] font-semibold text-slate-400 uppercase truncate" x-text="p.category_name || p.category?.name || 'Umum'"></span>
                                <span x-show="p.stock !== undefined" class="text-[9px] font-bold px-1.5 py-0.5 rounded"
                                      :class="p.stock > 5 ? 'bg-slate-200 text-slate-600' : 'bg-rose-100 text-rose-600'"
                                      x-text="'Stok: ' + p.stock"></span>
                            </div>
                            <h4 class="text-xs font-bold text-slate-800 line-clamp-2 uppercase group-hover:text-emerald-700 mt-1" x-text="p.name"></h4>
                        </div>
                        <div class="pt-2 border-t border-slate-200/60 flex justify-between items-center">
                            <span class="text-xs font-extrabold text-emerald-600" x-text="formatRupiah(p.price)"></span>
                            <span class="w-6 h-6 rounded-lg bg-emerald-600 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            </span>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- KOLOM KANAN: KERANJANG & PEMBAYARAN (5 COL) -->
    <div class="lg:col-span-5 bg-white rounded-2xl border border-slate-200/80 shadow-sm flex flex-col h-full overflow-hidden">

        <!-- Header Keranjang & Pilih Customer -->
        <div class="p-4 border-b border-slate-100 space-y-3 bg-slate-50/50">
            <div class="flex justify-between items-center">
                <h2 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 0a2 2 0 100 4 2 2 0 000-4z"/></svg>
                    <span>Keranjang Belanja</span>
                </h2>
                <button @click="clearCart()" x-show="cart.length > 0" class="text-[11px] text-rose-500 font-semibold hover:underline">
                    Kosongkan
                </button>
            </div>

            <!-- Select Member / Pelanggan (Opsional) -->
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Pelanggan / Member (Opsional)</label>
                <select x-model="selectedCustomerId" class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <option value="">-- Pelanggan Umum --</option>
                    <template x-for="c in customers" :key="c.id">
                        <option :value="c.id" x-text="c.name + (c.phone ? ' (' + c.phone + ')' : '')"></option>
                    </template>
                </select>
            </div>
        </div>

        <!-- List Item Keranjang -->
        <div class="flex-1 overflow-y-auto p-4 divide-y divide-slate-100">
            <template x-if="cart.length === 0">
                <div class="h-full flex flex-col items-center justify-center text-slate-400 space-y-1">
                    <svg class="w-12 h-12 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    <p class="text-xs font-semibold text-slate-400">Keranjang masih kosong</p>
                    <p class="text-[10px] text-slate-300">Klik produk di katalog untuk menambahkan</p>
                </div>
            </template>

            <template x-for="(item, index) in cart" :key="item.product_id">
                <div class="py-2.5 flex items-center justify-between gap-2">
                    <div class="flex-1 min-w-0">
                        <h5 class="text-xs font-bold text-slate-800 truncate uppercase" x-text="item.name"></h5>
                        <p class="text-[11px] text-slate-500" x-text="formatRupiah(item.price) + ' / item'"></p>
                    </div>

                    <!-- Counter Qty -->
                    <div class="flex items-center border border-slate-200 rounded-lg overflow-hidden bg-slate-50">
                        <button @click="updateQty(index, -1)" class="px-2 py-1 text-slate-600 hover:bg-slate-200 font-bold">-</button>
                        <span class="px-2.5 py-1 text-xs font-extrabold text-slate-800" x-text="item.quantity"></span>
                        <button @click="updateQty(index, 1)" class="px-2 py-1 text-slate-600 hover:bg-slate-200 font-bold">+</button>
                    </div>

                    <!-- Subtotal & Remove -->
                    <div class="text-right min-w-[70px]">
                        <span class="block text-xs font-bold text-slate-800" x-text="formatRupiah(item.price * item.quantity)"></span>
                        <button @click="removeItem(index)" class="text-[10px] text-rose-500 hover:underline">Hapus</button>
                    </div>
                </div>
            </template>
        </div>

        <!-- Total & Tombol Bayar -->
        <div class="p-4 bg-slate-50 border-t border-slate-100 space-y-3">
            <div class="space-y-1.5 text-xs">
                <div class="flex justify-between text-slate-500">
                    <span>Total Item</span>
                    <span class="font-bold text-slate-700" x-text="totalQty() + ' Pcs'"></span>
                </div>
                <div class="flex justify-between text-slate-800 font-extrabold text-base pt-1 border-t border-slate-200/60">
                    <span>Total Belanja</span>
                    <span class="text-emerald-600" x-text="formatRupiah(totalAmount())"></span>
                </div>
            </div>

            <button @click="openPaymentModal()"
                    :disabled="cart.length === 0"
                    class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 disabled:bg-slate-300 text-white font-bold rounded-xl shadow-md shadow-emerald-600/20 transition flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span>Bayar Sekarang</span>
            </button>
        </div>
    </div>

    <!-- ================= MODAL PEMBAYARAN ================= -->
    <div x-show="showPaymentModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
        <div class="bg-white w-full max-w-md rounded-2xl shadow-xl overflow-hidden" @click.away="showPaymentModal = false">
            <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-slate-800 text-sm">Proses Pembayaran</h3>
                <button @click="showPaymentModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
            </div>

            <div class="p-5 space-y-4">
                <!-- Nominal Total -->
                <div class="bg-emerald-50 p-4 rounded-xl border border-emerald-100 text-center">
                    <span class="text-[11px] font-bold text-emerald-600 uppercase">Total Yang Harus Dibayar</span>
                    <h2 class="text-2xl font-black text-emerald-700 mt-0.5" x-text="formatRupiah(totalAmount())"></h2>
                </div>

                <!-- Metode Pembayaran -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Metode Pembayaran</label>
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button" @click="paymentMethod = 'cash'"
                                :class="paymentMethod === 'cash' ? 'bg-emerald-600 text-white font-bold' : 'bg-slate-100 text-slate-700'"
                                class="py-2 rounded-xl text-xs uppercase font-semibold transition">Tunai</button>
                        <button type="button" @click="paymentMethod = 'qris'"
                                :class="paymentMethod === 'qris' ? 'bg-emerald-600 text-white font-bold' : 'bg-slate-100 text-slate-700'"
                                class="py-2 rounded-xl text-xs uppercase font-semibold transition">QRIS</button>
                        <button type="button" @click="paymentMethod = 'debit'"
                                :class="paymentMethod === 'debit' ? 'bg-emerald-600 text-white font-bold' : 'bg-slate-100 text-slate-700'"
                                class="py-2 rounded-xl text-xs uppercase font-semibold transition">Debit</button>
                    </div>
                </div>

                <!-- Input Uang Diterima -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nominal Uang Diterima (Rp)</label>
                    <input type="number"
                           x-model.number="cashPaid"
                           placeholder="0"
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-base font-extrabold text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">

                    <!-- Quick Amount Buttons -->
                    <div class="flex gap-2 mt-2">
                        <button @click="cashPaid = totalAmount()" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 rounded-lg text-[10px] font-bold text-slate-700">Uang Pas</button>
                        <button @click="cashPaid = 50000" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 rounded-lg text-[10px] font-bold text-slate-700">50.000</button>
                        <button @click="cashPaid = 100000" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 rounded-lg text-[10px] font-bold text-slate-700">100.000</button>
                    </div>
                </div>

                <!-- Kembalian -->
                <div class="flex justify-between items-center pt-2 border-t border-slate-100 text-xs">
                    <span class="font-bold text-slate-600">Uang Kembalian</span>
                    <span class="font-extrabold text-slate-800 text-sm"
                          :class="hitKembalian() < 0 ? 'text-rose-500' : 'text-emerald-600'"
                          x-text="formatRupiah(hitKembalian())"></span>
                </div>
            </div>

            <div class="p-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-2">
                <button @click="showPaymentModal = false" class="px-4 py-2 bg-slate-200 text-slate-700 font-bold text-xs rounded-xl">Batal</button>
                <button @click="submitSale()"
                        :disabled="isSubmitting || hitKembalian() < 0"
                        class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 disabled:bg-slate-300 text-white font-bold text-xs rounded-xl flex items-center gap-1.5">
                    <svg class="w-4 h-4" :class="isSubmitting ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>Selesaikan Transaksi</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ================= MODAL SUKSES & CETAK PDF ================= -->
    <div x-show="showSuccessModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
        <div class="bg-white w-full max-w-sm rounded-2xl shadow-xl p-6 text-center space-y-4">
            <div class="w-14 h-14 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div>
                <h3 class="font-extrabold text-slate-800 text-base">Transaksi Berhasil!</h3>
                <p class="text-xs text-slate-500 mt-1" x-text="'No. Invoice: ' + lastSaleResponse?.invoice_number"></p>
            </div>

            <div class="pt-2 flex flex-col gap-2">
                <a :href="'/penjualan/' + lastSaleResponse?.id + '/cetak-pdf'" target="_blank"
                   class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl flex items-center justify-center gap-2 shadow-md shadow-emerald-600/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    <span>Cetak Struk PDF</span>
                </a>
                <button @click="closeSuccessModal()" class="w-full py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl">
                    Transaksi Baru
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function posManager() {
        return {
            products: [],
            customers: [],
            cart: [],
            searchProduct: '',
            selectedCustomerId: '',
            paymentMethod: 'cash',
            cashPaid: 0,
            isLoadingProducts: false,
            showPaymentModal: false,
            showSuccessModal: false,
            isSubmitting: false,
            lastSaleResponse: null,

            async init() {
                await this.loadProducts();
                await this.loadCustomers();
            },

            async loadProducts() {
                this.isLoadingProducts = true;
                try {
                    const res = await fetch("{{ route('penjualan.produk') }}");
                    const json = await res.json();
                    this.products = json.data || json || [];
                } catch (e) {
                    console.error('Gagal memuat produk:', e);
                } finally {
                    this.isLoadingProducts = false;
                }
            },

            async loadCustomers() {
                try {
                    const res = await fetch("{{ route('penjualan.pelanggan') }}");
                    const json = await res.json();
                    this.customers = json.data || json || [];
                } catch (e) {
                    console.error('Gagal memuat pelanggan:', e);
                }
            },

            filteredProducts() {
                if (!this.searchProduct) return this.products;
                const query = this.searchProduct.toLowerCase();
                return this.products.filter(p => {
                    const matchName = p.name ? p.name.toLowerCase().includes(query) : false;
                    const matchBarcode = p.barcode ? p.barcode.toLowerCase().includes(query) : false;
                    return matchName || matchBarcode;
                });
            },

            addToCart(product) {
                const existing = this.cart.find(i => i.product_id === product.id);
                if (existing) {
                    existing.quantity++;
                } else {
                    this.cart.push({
                        product_id: product.id,
                        name: product.name,
                        price: product.price || 0,
                        quantity: 1
                    });
                }
            },

            updateQty(index, delta) {
                this.cart[index].quantity += delta;
                if (this.cart[index].quantity <= 0) {
                    this.cart.splice(index, 1);
                }
            },

            removeItem(index) {
                this.cart.splice(index, 1);
            },

            clearCart() {
                this.cart = [];
                this.selectedCustomerId = '';
            },

            totalQty() {
                return this.cart.reduce((acc, i) => acc + i.quantity, 0);
            },

            totalAmount() {
                return this.cart.reduce((acc, i) => acc + (i.price * i.quantity), 0);
            },

            openPaymentModal() {
                if (this.cart.length === 0) return;
                this.cashPaid = this.totalAmount();
                this.showPaymentModal = true;
            },

            hitKembalian() {
                return (this.cashPaid || 0) - this.totalAmount();
            },

            async submitSale() {
                if (this.cart.length === 0 || this.hitKembalian() < 0) return;

                this.isSubmitting = true;

                // Payload persis sesuai kontrak API Node.js backend
                const payload = {
                    items: this.cart.map(i => ({
                        product_id: i.product_id,
                        quantity: i.quantity
                    })),
                    payments: [
                        {
                            method: this.paymentMethod,
                            amount: Number(this.cashPaid)
                        }
                    ]
                };

                // Sertakan customer_id jika ada/dipilih (opsional)
                if (this.selectedCustomerId) {
                    payload.customer_id = this.selectedCustomerId;
                }

                try {
                    const res = await fetch("{{ route('penjualan.store') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });

                    const json = await res.json();

                    if (!res.ok) {
                        alert('Gagal menyimpan transaksi: ' + (json.meta?.message || json.message || 'Error server'));
                        return;
                    }

                    this.lastSaleResponse = json.data || json;
                    this.showPaymentModal = false;
                    this.showSuccessModal = true;
                    this.clearCart();

                } catch (e) {
                    alert('Terjadi kesalahan jaringan: ' + e.message);
                } finally {
                    this.isSubmitting = false;
                }
            },

            closeSuccessModal() {
                this.showSuccessModal = false;
                this.lastSaleResponse = null;
            },

            formatRupiah(number) {
                if (!number && number !== 0) return 'Rp 0';
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(number);
            }
        }
    }
</script>
@endpush

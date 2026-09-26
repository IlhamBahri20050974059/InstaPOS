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

@section('title', 'Penjualan (POS)')

@section('content')
@php
    $userSession = session('user');
    $namaKasir = is_array($userSession)
        ? ($userSession['name'] ?? $userSession['username'] ?? 'Kasir Utama')
        : ($userSession->name ?? $userSession->username ?? 'Kasir Utama');
@endphp

<div x-data="posSystem('{{ $namaKasir }}')" x-init="initPOS()" class="h-[calc(100vh-5rem)] flex flex-col md:flex-row gap-5 overflow-hidden">

    <!-- ================= KOLOM KIRI: KATALOG & PENCARIAN PRODUK (60%) ================= -->
    <div class="flex-1 flex flex-col bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">

        <!-- Top Bar Search -->
        <div class="p-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between gap-4">
            <div class="relative flex-1">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </span>
                <input type="text"
                       x-model="searchQuery"
                       placeholder="Cari nama produk / scan barcode..."
                       class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-300 rounded-xl text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition">
            </div>

            <!-- Reload API Button -->
            <button @click="fetchProducts()"
                    class="p-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl transition flex items-center gap-1.5 text-xs font-semibold"
                    title="Refresh Produk">
                <svg class="w-4 h-4" :class="isLoading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <span class="hidden sm:inline">Refresh</span>
            </button>
        </div>

        <!-- Grid Produk -->
        <div class="flex-1 overflow-y-auto p-4">
            <!-- State Loading -->
            <template x-if="isLoading">
                <div class="flex flex-col items-center justify-center h-full text-slate-400 space-y-2 py-12">
                    <svg class="w-8 h-8 animate-spin text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <p class="text-xs font-medium">Memuat data produk dari API...</p>
                </div>
            </template>

            <!-- State Error Request API -->
            <template x-if="!isLoading && errorMessage">
                <div class="flex flex-col items-center justify-center h-full text-red-500 space-y-2 py-12">
                    <svg class="w-10 h-10 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-xs font-bold" x-text="errorMessage"></p>
                    <button @click="fetchProducts()" class="text-[11px] underline text-slate-600 hover:text-slate-800">Coba Lagi</button>
                </div>
            </template>

            <!-- Grid Tampilan Produk -->
            <template x-if="!isLoading && !errorMessage">
                <div>
                    <!-- State Jika Produk Kosong / Tidak Ditemukan -->
                    <template x-if="filteredProducts().length === 0">
                        <div class="flex flex-col items-center justify-center h-full text-slate-400 space-y-2 py-16">
                            <svg class="w-12 h-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            <p class="text-xs font-semibold text-slate-500">Tidak ada produk ditemukan</p>
                        </div>
                    </template>

                    <!-- Kartu Produk -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                        <template x-for="product in filteredProducts()" :key="product.id">
                            <div @click="addToCart(product)"
                                 class="relative p-3.5 rounded-xl border transition flex flex-col justify-between cursor-pointer select-none group"
                                 :class="{
                                     'bg-slate-50 border-slate-200 opacity-60 cursor-not-allowed': product.stock <= 0 || !product.is_active,
                                     'bg-white border-slate-200 hover:border-emerald-500 hover:shadow-md': product.stock > 0 && product.is_active
                                 }">

                                <div>
                                    <!-- Header Badge Stok -->
                                    <div class="flex justify-between items-start gap-1 mb-2">
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-md"
                                              :class="product.stock > 0 && product.is_active ? 'bg-slate-100 text-slate-600' : 'bg-red-100 text-red-700'">
                                            <span x-text="product.stock > 0 && product.is_active ? 'Stok: ' + product.stock : 'Stok Habis'"></span>
                                        </span>
                                    </div>

                                    <!-- Nama Produk -->
                                    <h4 class="text-xs font-bold text-slate-800 line-clamp-2 mb-2 group-hover:text-emerald-600 transition" x-text="product.name"></h4>
                                </div>

                                <!-- Harga Produk -->
                                <div class="mt-2 pt-2 border-t border-slate-100">
                                    <p class="text-xs font-black text-emerald-600" x-text="formatRupiah(product.price)"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>

    </div>

    <!-- ================= KOLOM KANAN: KERANJANG & PEMBAYARAN (40%) ================= -->
    <div class="w-full md:w-96 flex flex-col bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden shrink-0">

        <!-- Header Keranjang -->
        <div class="p-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
            <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                Keranjang Belanja
            </h3>
            <button @click="clearCart()" x-show="cart.length > 0" class="text-[11px] font-semibold text-red-600 hover:text-red-800">
                Kosongkan
            </button>
        </div>

        <!-- Daftar Item Keranjang -->
        <div class="flex-1 overflow-y-auto p-4 divide-y divide-slate-100">
            <template x-if="cart.length === 0">
                <div class="flex flex-col items-center justify-center h-full text-slate-400 py-12">
                    <svg class="w-12 h-12 text-slate-200 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    <p class="text-xs font-medium">Keranjang masih kosong</p>
                    <p class="text-[10px] text-slate-400 mt-0.5">Klik produk di sebelah kiri untuk memilih</p>
                </div>
            </template>

            <template x-for="(item, index) in cart" :key="item.id">
                <div class="py-3 first:pt-0 last:pb-0 flex items-center justify-between gap-2">
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-bold text-slate-800 truncate" x-text="item.name"></p>
                        <p class="text-[11px] text-slate-500 font-mono" x-text="formatRupiah(item.price) + ' × ' + item.qty"></p>
                    </div>

                    <!-- Kontrol Qty (+ / -) -->
                    <div class="flex items-center space-x-1.5 bg-slate-100 p-1 rounded-lg">
                        <button @click="updateQty(index, -1)" class="w-5 h-5 bg-white rounded text-slate-700 hover:bg-slate-200 flex items-center justify-center font-bold text-xs">-</button>
                        <span class="text-xs font-bold w-5 text-center" x-text="item.qty"></span>
                        <button @click="updateQty(index, 1)" class="w-5 h-5 bg-white rounded text-slate-700 hover:bg-slate-200 flex items-center justify-center font-bold text-xs">+</button>
                    </div>

                    <!-- Total harga item & Hapus -->
                    <div class="text-right">
                        <p class="text-xs font-bold text-slate-800" x-text="formatRupiah(item.price * item.qty)"></p>
                        <button @click="removeFromCart(index)" class="text-[10px] text-red-500 hover:underline">Hapus</button>
                    </div>
                </div>
            </template>
        </div>

        <!-- Area Ringkasan Pembayaran -->
        <div class="p-4 border-t border-slate-200 bg-slate-50/80 space-y-3">

            <!-- Total Harga -->
            <div class="flex justify-between items-center text-sm">
                <span class="font-bold text-slate-600">Total Belanja</span>
                <span class="text-lg font-black text-emerald-600" x-text="formatRupiah(calculateTotal())"></span>
            </div>

            <!-- Pilihan Metode Pembayaran -->
            <div class="space-y-1">
                <label class="text-[11px] font-bold text-slate-500 uppercase">Metode Pembayaran</label>
                <div class="grid grid-cols-3 gap-1.5">
                    <template x-for="method in ['Tunai', 'QRIS', 'Debit']">
                        <button @click="paymentMethod = method; if(method !== 'Tunai') paidAmount = calculateTotal()"
                                :class="paymentMethod === method ? 'bg-emerald-600 text-white font-bold' : 'bg-white text-slate-700 border border-slate-300'"
                                class="py-1.5 rounded-lg text-xs transition text-center"
                                x-text="method">
                        </button>
                    </template>
                </div>
            </div>

            <!-- Input Uang Bayar (Khusus Tunai) -->
            <div class="space-y-1" x-show="paymentMethod === 'Tunai'">
                <label class="text-[11px] font-bold text-slate-500 uppercase">Nominal Bayar (Rp)</label>
                <input type="number"
                       x-model.number="paidAmount"
                       placeholder="0"
                       class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">

                <!-- Quick Amount Buttons -->
                <div class="flex gap-1 pt-1 overflow-x-auto">
                    <button @click="paidAmount = calculateTotal()" class="px-2 py-1 bg-slate-200 hover:bg-slate-300 rounded text-[10px] font-semibold text-slate-700 whitespace-nowrap">Uang Pas</button>
                    <button @click="paidAmount = 50000" class="px-2 py-1 bg-slate-200 hover:bg-slate-300 rounded text-[10px] font-semibold text-slate-700">50k</button>
                    <button @click="paidAmount = 100000" class="px-2 py-1 bg-slate-200 hover:bg-slate-300 rounded text-[10px] font-semibold text-slate-700">100k</button>
                </div>
            </div>

            <!-- Uang Kembalian -->
            <div class="flex justify-between items-center text-xs pt-1 border-t border-slate-200/60" x-show="paymentMethod === 'Tunai'">
                <span class="font-semibold text-slate-500">Kembalian</span>
                <span class="font-bold" :class="calculateChange() >= 0 ? 'text-slate-800' : 'text-red-500'" x-text="formatRupiah(calculateChange())"></span>
            </div>

            <!-- Tombol Cetak Struk -->
            <button @click="processPayment()"
                    :disabled="cart.length === 0 || (paymentMethod === 'Tunai' && paidAmount < calculateTotal())"
                    class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 disabled:bg-slate-300 text-white font-bold text-xs rounded-xl transition shadow-lg shadow-emerald-600/20 disabled:shadow-none flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2m2 4h6a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2zm8-12V5a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4h10z"/></svg>
                <span>Bayar & Cetak Struk (PDF)</span>
            </button>

        </div>

    </div>

    <!-- ================= TEMPLATE THERMAL STRUK (LEBAR 58MM) UNTUK WINDOW PRINT ================= -->
    <div id="thermal-receipt" class="hidden">
        <style>
            @media print {
                body * { visibility: hidden; }
                #thermal-receipt, #thermal-receipt * { visibility: visible; }
                #thermal-receipt {
                    position: absolute;
                    left: 0;
                    top: 0;
                    width: 58mm;
                    font-family: 'Courier New', Courier, monospace;
                    font-size: 9pt;
                    color: #000;
                    padding: 2mm;
                    background: #fff;
                }
                .text-center { text-align: center; }
                .text-right { text-align: right; }
                .border-dash { border-bottom: 1px dashed #000; margin: 4px 0; }
                .flex-row { display: flex; justify-content: space-between; }
            }
        </style>

        <div class="text-center">
            <h2 style="font-size: 11pt; font-weight: bold; margin: 0;">INSTAPOS MINIMARKET</h2>
            <p style="margin: 2px 0;">Telp: 087712153048</p>
            <p style="margin: 2px 0;">ilhambahri@gmail.com</p>
        </div>

        <div class="border-dash"></div>

        <div>
            <div class="flex-row">
                <span>No:</span>
                <span x-text="receiptData.noStruk"></span>
            </div>
            <div class="flex-row">
                <span>Tgl:</span>
                <span x-text="receiptData.tanggal"></span>
            </div>
            <div class="flex-row">
                <span>Kasir:</span>
                <span x-text="receiptData.kasir"></span>
            </div>
        </div>

        <div class="border-dash"></div>

        <!-- Daftar Items -->
        <template x-for="item in receiptData.items" :key="item.id">
            <div style="margin-bottom: 3px;">
                <div x-text="item.name" style="font-weight: bold;"></div>
                <div class="flex-row">
                    <span x-text="item.qty + ' x ' + formatRupiah(item.price)"></span>
                    <span x-text="formatRupiah(item.qty * item.price)"></span>
                </div>
            </div>
        </template>

        <div class="border-dash"></div>

        <!-- Total Financial -->
        <div>
            <div class="flex-row" style="font-weight: bold;">
                <span>TOTAL:</span>
                <span x-text="formatRupiah(receiptData.total)"></span>
            </div>
            <div class="flex-row">
                <span>BAYAR (<span x-text="receiptData.metode"></span>):</span>
                <span x-text="formatRupiah(receiptData.bayar)"></span>
            </div>
            <div class="flex-row" x-show="receiptData.metode === 'Tunai'">
                <span>KEMBALI:</span>
                <span x-text="formatRupiah(receiptData.kembali)"></span>
            </div>
        </div>

        <div class="border-dash"></div>

        <div class="text-center" style="margin-top: 6px;">
            <p style="margin: 2px 0;">Terima kasih telah berbelanja!</p>
            <p style="margin: 2px 0;">Barang yang sudah dibeli</p>
            <p style="margin: 2px 0;">tidak dapat ditukar/dikembalikan.</p>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function posSystem(namaKasir) {
        return {
            kasirName: namaKasir,
            products: [],
            searchQuery: '',
            isLoading: false,
            errorMessage: '',
            cart: [],
            paymentMethod: 'Tunai',
            paidAmount: 0,
            receiptData: {
                noStruk: '',
                tanggal: '',
                kasir: '',
                items: [],
                total: 0,
                bayar: 0,
                kembali: 0,
                metode: ''
            },

            formatRupiah(number) {
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(number);
            },

            initPOS() {
                this.fetchProducts();
            },

            async fetchProducts() {
                this.isLoading = true;
                this.errorMessage = '';
                try {
                    const response = await fetch("{{ route('penjualan.get-products') }}", {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const json = await response.json();

                    if (!response.ok) {
                        throw new Error(json.meta?.message || `Error HTTP: ${response.status}`);
                    }

                    if (json.data) {
                        this.products = json.data;
                    }
                } catch (error) {
                    console.error('Gagal mengambil data produk:', error);
                    this.errorMessage = error.message;
                } finally {
                    this.isLoading = false;
                }
            },

            filteredProducts() {
                if (!this.searchQuery) return this.products;
                return this.products.filter(p => p.name.toLowerCase().includes(this.searchQuery.toLowerCase()));
            },

            addToCart(product) {
                if (product.stock <= 0 || !product.is_active) {
                    alert('Produk ini stoknya habis dan tidak dapat dibeli.');
                    return;
                }

                const existingIndex = this.cart.findIndex(item => item.id === product.id);
                if (existingIndex > -1) {
                    if (this.cart[existingIndex].qty + 1 > product.stock) {
                        alert('Jumlah dalam keranjang tidak boleh melebihi stok yang ada (' + product.stock + ').');
                        return;
                    }
                    this.cart[existingIndex].qty++;
                } else {
                    this.cart.push({
                        id: product.id,
                        name: product.name,
                        price: product.price,
                        qty: 1,
                        maxStock: product.stock
                    });
                }
            },

            updateQty(index, delta) {
                const item = this.cart[index];
                const newQty = item.qty + delta;

                if (newQty > item.maxStock) {
                    alert('Jumlah tidak boleh melebihi stok yang ada (' + item.maxStock + ').');
                    return;
                }

                if (newQty <= 0) {
                    this.removeFromCart(index);
                } else {
                    item.qty = newQty;
                }
            },

            removeFromCart(index) {
                this.cart.splice(index, 1);
            },

            clearCart() {
                this.cart = [];
                this.paidAmount = 0;
            },

            calculateTotal() {
                return this.cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
            },

            calculateChange() {
                return this.paidAmount - this.calculateTotal();
            },

            processPayment() {
                const total = this.calculateTotal();

                if (this.paymentMethod === 'Tunai' && this.paidAmount < total) {
                    alert('Nominal pembayaran kurang!');
                    return;
                }

                const now = new Date();
                const pad = (n) => n.toString().padStart(2, '0');
                const tglString = `${pad(now.getDate())}/${pad(now.getMonth()+1)}/${now.getFullYear()} ${pad(now.getHours())}:${pad(now.getMinutes())}`;
                const noStruk = 'TRX-' + now.getFullYear() + pad(now.getMonth()+1) + pad(now.getDate()) + '-' + Math.floor(1000 + Math.random() * 9000);

                this.receiptData = {
                    noStruk: noStruk,
                    tanggal: tglString,
                    kasir: this.kasirName,
                    items: JSON.parse(JSON.stringify(this.cart)),
                    total: total,
                    bayar: this.paymentMethod === 'Tunai' ? this.paidAmount : total,
                    kembali: this.paymentMethod === 'Tunai' ? this.calculateChange() : 0,
                    metode: this.paymentMethod
                };

                this.$nextTick(() => {
                    window.print();
                    this.clearCart();
                });
            }
        }
    }
</script>
@endpush

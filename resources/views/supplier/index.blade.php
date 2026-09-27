@php
    $userSession = session('user');

    // Ambil role user dari session
    $role = is_array($userSession)
        ? ($userSession['role'] ?? $userSession['level'] ?? 'cashier')
        : ($userSession->role ?? $userSession->level ?? 'cashier');

    // Tentukan layout berdasarkan role
    $layout = (in_array(strtolower($role), ['supervisor', 'spv', 'admin']))
        ? 'layouts.supervisor'
        : 'layouts.app';
@endphp
@extends($layout)

@section('title', 'Manajemen Supplier')

@section('content')
<div x-data="supplierManager()" x-init="init()" class="space-y-5">

    <!-- Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
        <div>
            <h1 class="text-lg font-bold text-slate-800">Daftar Supplier</h1>
            <p class="text-xs text-slate-500 mt-0.5">Kelola data vendor dan pemasok barang minimarket.</p>
        </div>

        <div class="flex items-center gap-2">
            <button @click="loadData()"
                    class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition flex items-center gap-2 text-xs font-semibold">
                <svg class="w-4 h-4" :class="isLoading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span>Refresh</span>
            </button>

            <button @click="openCreateModal()"
                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl transition flex items-center gap-2 text-xs font-bold shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                <span>Tambah Supplier</span>
            </button>
        </div>
    </div>

    <!-- Stat Cards Ringkasan -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m3 0h1m-4-8l-2-2m0 0l-2 2m2-2v6"/></svg>
            </div>
            <div>
                <p class="text-[11px] font-semibold text-slate-400">Total Supplier Registered</p>
                <h3 class="text-lg font-extrabold text-slate-800" x-text="suppliers.length">0</h3>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
            </div>
            <div>
                <p class="text-[11px] font-semibold text-slate-400">Kontak Aktif</p>
                <h3 class="text-lg font-extrabold text-slate-800" x-text="suppliers.filter(s => s.phone_number).length">0</h3>
            </div>
        </div>
    </div>

    <!-- Toolbar Pencarian -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
        <div class="relative w-full md:w-80">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </span>
            <input type="text"
                   x-model="searchQuery"
                   placeholder="Cari Kode / Nama Supplier..."
                   class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:bg-white transition">
        </div>
    </div>

    <!-- Tabel Data Supplier -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">

        <!-- Loading State -->
        <template x-if="isLoading">
            <div class="p-12 flex flex-col items-center justify-center text-slate-400 space-y-3">
                <svg class="w-8 h-8 animate-spin text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <p class="text-xs font-semibold">Memuat data supplier...</p>
            </div>
        </template>

        <!-- Table View -->
        <template x-if="!isLoading">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            <th class="py-3.5 px-4">Kode</th>
                            <th class="py-3.5 px-4">Nama Supplier</th>
                            <th class="py-3.5 px-4">No. Telepon</th>
                            <th class="py-3.5 px-4">Alamat</th>
                            <th class="py-3.5 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">

                        <!-- Empty State -->
                        <template x-if="filteredSuppliers().length === 0">
                            <tr>
                                <td colspan="5" class="py-12 text-center text-slate-400">
                                    <svg class="w-10 h-10 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m3 0h1m-4-8l-2-2m0 0l-2 2m2-2v6"/></svg>
                                    <p class="font-semibold text-slate-500">Supplier tidak ditemukan</p>
                                </td>
                            </tr>
                        </template>

                        <!-- Loop Data -->
                        <template x-for="supplier in filteredSuppliers()" :key="supplier.id">
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-4 font-mono font-bold text-slate-800" x-text="supplier.code || '-'"></td>
                                <td class="py-3.5 px-4 font-bold text-slate-800" x-text="supplier.name"></td>
                                <td class="py-3.5 px-4 text-slate-600 font-mono">
                                    <div class="flex items-center gap-1.5">
                                        <span x-text="supplier.phone_number || '-'"></span>
                                        <template x-if="supplier.phone_number">
                                            <a :href="'https://wa.me/' + formatWA(supplier.phone_number)"
                                               target="_blank"
                                               class="p-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 rounded-md transition"
                                               title="Hubungi via WhatsApp">
                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                                            </a>
                                        </template>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-slate-600 max-w-xs truncate" x-text="supplier.address || '-'"></td>
                                <td class="py-3.5 px-4 text-center">
                                    <button @click="openEditModal(supplier)"
                                            class="px-2.5 py-1.5 bg-slate-100 hover:bg-emerald-50 text-slate-600 hover:text-emerald-600 rounded-lg transition font-semibold text-xs inline-flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        <span>Edit</span>
                                    </button>
                                </td>
                            </tr>
                        </template>

                    </tbody>
                </table>
            </div>
        </template>

    </div>

    <!-- ================= MODAL TAMBAH SUPPLIER ================= -->
    <div x-show="showCreateModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
        <div class="bg-white w-full max-w-md rounded-2xl shadow-xl overflow-hidden" @click.away="showCreateModal = false">
            <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <h3 class="font-bold text-slate-800 text-sm">Tambah Supplier Baru</h3>
                <button @click="showCreateModal = false" class="p-1 text-slate-400 hover:text-slate-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>

            <form @submit.prevent="submitCreate()" class="p-4 space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Kode Supplier <span class="text-rose-500">*</span></label>
                    <input type="text" x-model="createForm.code" required placeholder="Contoh: SUP001" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono uppercase focus:ring-2 focus:ring-emerald-500 focus:bg-white">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama Supplier / PT <span class="text-rose-500">*</span></label>
                    <input type="text" x-model="createForm.name" required placeholder="Contoh: PT Sumber Makmur" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-emerald-500 focus:bg-white">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">No. Telepon / WhatsApp <span class="text-rose-500">*</span></label>
                    <input type="text" x-model="createForm.phone_number" required placeholder="081234567890" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono focus:ring-2 focus:ring-emerald-500 focus:bg-white">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Alamat Kantor / Gudang</label>
                    <textarea x-model="createForm.address" rows="3" placeholder="Jl. Raya Gresik No. 10..." class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-emerald-500 focus:bg-white"></textarea>
                </div>

                <div class="pt-2 flex justify-end gap-2 border-t border-slate-100">
                    <button type="button" @click="showCreateModal = false" class="px-4 py-2 bg-slate-100 text-slate-600 font-semibold text-xs rounded-xl hover:bg-slate-200">Batal</button>
                    <button type="submit" :disabled="isSaving" class="px-4 py-2 bg-emerald-600 text-white font-bold text-xs rounded-xl hover:bg-emerald-700 flex items-center gap-1.5">
                        <span x-show="!isSaving">Simpan Supplier</span>
                        <span x-show="isSaving">Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ================= MODAL EDIT SUPPLIER ================= -->
    <div x-show="showEditModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
        <div class="bg-white w-full max-w-md rounded-2xl shadow-xl overflow-hidden" @click.away="showEditModal = false">
            <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <h3 class="font-bold text-slate-800 text-sm">Edit Supplier</h3>
                <button @click="showEditModal = false" class="p-1 text-slate-400 hover:text-slate-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>

            <form @submit.prevent="submitEdit()" class="p-4 space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Kode Supplier <span class="text-rose-500">*</span></label>
                    <input type="text" x-model="editForm.code" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono uppercase focus:ring-2 focus:ring-emerald-500 focus:bg-white">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama Supplier / PT <span class="text-rose-500">*</span></label>
                    <input type="text" x-model="editForm.name" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-emerald-500 focus:bg-white">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">No. Telepon / WhatsApp <span class="text-rose-500">*</span></label>
                    <input type="text" x-model="editForm.phone_number" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono focus:ring-2 focus:ring-emerald-500 focus:bg-white">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Alamat Kantor / Gudang</label>
                    <textarea x-model="editForm.address" rows="3" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-emerald-500 focus:bg-white"></textarea>
                </div>

                <div class="pt-2 flex justify-end gap-2 border-t border-slate-100">
                    <button type="button" @click="showEditModal = false" class="px-4 py-2 bg-slate-100 text-slate-600 font-semibold text-xs rounded-xl hover:bg-slate-200">Batal</button>
                    <button type="submit" :disabled="isSaving" class="px-4 py-2 bg-emerald-600 text-white font-bold text-xs rounded-xl hover:bg-emerald-700 flex items-center gap-1.5">
                        <span x-show="!isSaving">Update Supplier</span>
                        <span x-show="isSaving">Memperbarui...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function supplierManager() {
        return {
            suppliers: [],
            searchQuery: '',
            isLoading: false,
            isSaving: false,

            // Modal Controls
            showCreateModal: false,
            showEditModal: false,

            createForm: {
                code: '',
                name: '',
                phone_number: '',
                address: ''
            },

            editForm: {
                id: '',
                code: '',
                name: '',
                phone_number: '',
                address: ''
            },

            formatWA(phone) {
                if (!phone) return '';
                let cleaned = phone.replace(/[^0-9]/g, '');
                if (cleaned.startsWith('0')) {
                    cleaned = '62' + cleaned.substring(1);
                }
                return cleaned;
            },

            async init() {
                await this.loadData();
            },

            async loadData() {
                this.isLoading = true;
                try {
                    const res = await fetch("{{ route('suppliers.data') }}");
                    const json = await res.json();
                    this.suppliers = json.data || [];
                } catch (e) {
                    alert('Gagal mengambil data supplier');
                } finally {
                    this.isLoading = false;
                }
            },

            filteredSuppliers() {
                return this.suppliers.filter(s => {
                    const query = this.searchQuery.toLowerCase();
                    const codeMatch = s.code ? s.code.toLowerCase().includes(query) : false;
                    const nameMatch = s.name ? s.name.toLowerCase().includes(query) : false;
                    return !this.searchQuery || codeMatch || nameMatch;
                });
            },

            openCreateModal() {
                this.createForm = { code: '', name: '', phone_number: '', address: '' };
                this.showCreateModal = true;
            },

            async submitCreate() {
                this.isSaving = true;
                try {
                    const res = await fetch("{{ route('suppliers.store') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(this.createForm)
                    });

                    const json = await res.json();
                    if (!res.ok) throw new Error(json.meta?.message || 'Gagal menyimpan supplier.');

                    this.showCreateModal = false;
                    await this.loadData();
                    alert('Supplier berhasil ditambahkan!');
                } catch (e) {
                    alert(e.message);
                } finally {
                    this.isSaving = false;
                }
            },

            openEditModal(supplier) {
                this.editForm = {
                    id: supplier.id,
                    code: supplier.code,
                    name: supplier.name,
                    phone_number: supplier.phone_number,
                    address: supplier.address
                };
                this.showEditModal = true;
            },

            async submitEdit() {
                this.isSaving = true;
                try {
                    const url = "{{ route('suppliers.update', ':id') }}".replace(':id', this.editForm.id);
                    const res = await fetch(url, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(this.editForm)
                    });

                    const json = await res.json();
                    if (!res.ok) throw new Error(json.meta?.message || 'Gagal memperbarui supplier.');

                    this.showEditModal = false;
                    await this.loadData();
                    alert('Supplier berhasil diperbarui!');
                } catch (e) {
                    alert(e.message);
                } finally {
                    this.isSaving = false;
                }
            }
        }
    }
</script>
@endpush

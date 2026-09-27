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

@section('title', 'Manajemen Kategori')

@section('content')
<div x-data="kategoriManager()" x-init="init()" class="space-y-5">

    <!-- Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
        <div>
            <h1 class="text-lg font-bold text-slate-800">Katalog Kategori</h1>
            <p class="text-xs text-slate-500 mt-0.5">Kelola daftar pengelompokan produk dan item toko.</p>
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
                <span>Tambah Kategori</span>
            </button>
        </div>
    </div>

    <!-- Stat Cards Ringkasan -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
            </div>
            <div>
                <p class="text-[11px] font-semibold text-slate-400">Total Kategori</p>
                <h3 class="text-lg font-extrabold text-slate-800" x-text="categories.length">0</h3>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-[11px] font-semibold text-slate-400">Hasil Pencarian</p>
                <h3 class="text-lg font-extrabold text-slate-800" x-text="filteredCategories().length">0</h3>
            </div>
        </div>
    </div>

    <!-- Toolbar Pencarian -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
        <div class="relative w-full sm:w-80">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </span>
            <input type="text"
                   x-model="searchQuery"
                   placeholder="Cari Nama Kategori..."
                   class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:bg-white transition">
        </div>
    </div>

    <!-- Tabel Data Kategori -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">

        <!-- Loading State -->
        <template x-if="isLoading">
            <div class="p-12 flex flex-col items-center justify-center text-slate-400 space-y-3">
                <svg class="w-8 h-8 animate-spin text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <p class="text-xs font-semibold">Memuat data kategori...</p>
            </div>
        </template>

        <!-- Table View -->
        <template x-if="!isLoading">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            <th class="py-3.5 px-4 w-20 text-center">No</th>
                            <th class="py-3.5 px-4">Nama Kategori</th>
                            <th class="py-3.5 px-4 text-center w-36">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">

                        <!-- Empty Data -->
                        <template x-if="filteredCategories().length === 0">
                            <tr>
                                <td colspan="3" class="py-12 text-center text-slate-400">
                                    <svg class="w-10 h-10 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                    </svg>
                                    <p class="font-semibold text-slate-500">Kategori tidak ditemukan</p>
                                </td>
                            </tr>
                        </template>

                        <!-- Loop Kategori -->
                        <template x-for="(cat, index) in filteredCategories()" :key="cat.id">
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-4 text-center font-mono text-slate-500" x-text="index + 1"></td>
                                <td class="py-3.5 px-4 font-bold text-slate-800 uppercase" x-text="cat.name"></td>
                                <td class="py-3.5 px-4 text-center">
                                    <button @click="openEditModal(cat)"
                                            class="px-3 py-1.5 bg-slate-100 hover:bg-emerald-50 text-slate-600 hover:text-emerald-600 rounded-lg transition font-semibold text-xs inline-flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
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

    <!-- ================= MODAL TAMBAH KATEGORI ================= -->
    <div x-show="showCreateModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
        <div class="bg-white w-full max-w-md rounded-2xl shadow-xl overflow-hidden" @click.away="showCreateModal = false">
            <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <h3 class="font-bold text-slate-800 text-sm">Tambah Kategori Baru</h3>
                <button @click="showCreateModal = false" class="p-1 text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form @submit.prevent="submitCreate()" class="p-4 space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama Kategori <span class="text-rose-500">*</span></label>
                    <input type="text" x-model="createForm.name" required placeholder="Contoh: Makanan / Minuman" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-emerald-500 focus:bg-white">
                </div>

                <div class="pt-2 flex justify-end gap-2 border-t border-slate-100">
                    <button type="button" @click="showCreateModal = false" class="px-4 py-2 bg-slate-100 text-slate-600 font-semibold text-xs rounded-xl hover:bg-slate-200">Batal</button>
                    <button type="submit" :disabled="isSaving" class="px-4 py-2 bg-emerald-600 text-white font-bold text-xs rounded-xl hover:bg-emerald-700 flex items-center gap-1.5">
                        <span x-show="!isSaving">Simpan Kategori</span>
                        <span x-show="isSaving">Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ================= MODAL EDIT KATEGORI ================= -->
    <div x-show="showEditModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
        <div class="bg-white w-full max-w-md rounded-2xl shadow-xl overflow-hidden" @click.away="showEditModal = false">
            <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <h3 class="font-bold text-slate-800 text-sm">Edit Kategori</h3>
                <button @click="showEditModal = false" class="p-1 text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form @submit.prevent="submitEdit()" class="p-4 space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama Kategori <span class="text-rose-500">*</span></label>
                    <input type="text" x-model="editForm.name" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-emerald-500 focus:bg-white">
                </div>

                <div class="pt-2 flex justify-end gap-2 border-t border-slate-100">
                    <button type="button" @click="showEditModal = false" class="px-4 py-2 bg-slate-100 text-slate-600 font-semibold text-xs rounded-xl hover:bg-slate-200">Batal</button>
                    <button type="submit" :disabled="isSaving" class="px-4 py-2 bg-emerald-600 text-white font-bold text-xs rounded-xl hover:bg-emerald-700 flex items-center gap-1.5">
                        <span x-show="!isSaving">Update Kategori</span>
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
    function kategoriManager() {
        return {
            categories: [],
            searchQuery: '',
            isLoading: false,
            isSaving: false,

            // Modal Status
            showCreateModal: false,
            showEditModal: false,

            createForm: {
                name: ''
            },

            editForm: {
                id: '',
                name: ''
            },

            async init() {
                await this.loadData();
            },

            async loadData() {
                this.isLoading = true;
                try {
                    const res = await fetch("{{ route('kategori.data') }}");
                    const json = await res.json();
                    this.categories = json.data || json || [];
                } catch (e) {
                    alert('Gagal mengambil data kategori');
                } finally {
                    this.isLoading = false;
                }
            },

            filteredCategories() {
                return this.categories.filter(cat => {
                    const query = this.searchQuery.toLowerCase();
                    return !this.searchQuery || (cat.name && cat.name.toLowerCase().includes(query));
                });
            },

            openCreateModal() {
                this.createForm = { name: '' };
                this.showCreateModal = true;
            },

            async submitCreate() {
                this.isSaving = true;
                try {
                    const res = await fetch("{{ route('kategori.store') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(this.createForm)
                    });

                    const json = await res.json();
                    if (!res.ok) throw new Error(json.meta?.message || json.message || 'Gagal menyimpan kategori.');

                    this.showCreateModal = false;
                    await this.loadData();
                    alert('Kategori berhasil ditambahkan!');
                } catch (e) {
                    alert(e.message);
                } finally {
                    this.isSaving = false;
                }
            },

            openEditModal(category) {
                this.editForm = {
                    id: category.id,
                    name: category.name
                };
                this.showEditModal = true;
            },

            async submitEdit() {
                this.isSaving = true;
                try {
                    const url = "{{ route('kategori.update', ':id') }}".replace(':id', this.editForm.id);
                    const res = await fetch(url, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(this.editForm)
                    });

                    const json = await res.json();
                    if (!res.ok) throw new Error(json.meta?.message || json.message || 'Gagal memperbarui kategori.');

                    this.showEditModal = false;
                    await this.loadData();
                    alert('Kategori berhasil diperbarui!');
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

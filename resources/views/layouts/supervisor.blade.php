<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InstaPOS - System Minimarket</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Chart.js via Cloudflare -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
    <!-- Google Font: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 antialiased" x-data="{ sidebarOpen: true }">

    <div class="min-h-screen flex">

        <!-- ================= SIDEBAR SUPERVISOR ================= -->
        <aside class="bg-slate-900 text-slate-300 flex-shrink-0 transition-all duration-300 z-30 min-h-screen sticky top-0 h-screen"
               :class="sidebarOpen ? 'w-64' : 'w-20'">

            <div class="flex flex-col h-full justify-between">

                <!-- BAGIAN ATAS: LOGO & NAVIGASI -->
                <div>
                    <!-- Header Brand Sidebar -->
                    <div class="h-16 flex items-center px-4 border-b border-slate-800 justify-between">
                        <div class="flex items-center space-x-3 overflow-hidden" x-show="sidebarOpen">
                            <span class="font-bold text-white tracking-wide text-base truncate">INSTA</span>
                            <div class="bg-emerald-600 text-white font-black p-2 rounded-lg text-sm shrink-0">POS</div>
                        </div>
                        <button @click="sidebarOpen = !sidebarOpen" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 focus:outline-none">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Menu Utama Supervisor -->
                    <nav class="mt-4 px-3 space-y-1.5">

                        <!-- 1. Dashboard -->
                        <a href="{{ request()->is('dashboard') ? '#' : url('/dashboard') }}"
                           class="flex items-center px-3 py-2.5 rounded-xl transition text-sm font-medium {{ request()->is('dashboard') || request()->is('/') ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30' : 'hover:bg-slate-800 hover:text-white text-slate-400' }}">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            <span class="ml-3 truncate" x-show="sidebarOpen">Dashboard</span>
                        </a>

                        <!-- 2. Produk (BARU) -->
                        <a href="{{ url('/produk') }}"
                           class="flex items-center px-3 py-2.5 rounded-xl transition text-sm font-medium {{ request()->is('produk*') ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30' : 'hover:bg-slate-800 hover:text-white text-slate-400' }}">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            <span class="ml-3 truncate" x-show="sidebarOpen">Produk</span>
                        </a>

                        <!-- 3. Supplier (BARU) -->
                        <a href="{{ url('/supplier') }}"
                           class="flex items-center px-3 py-2.5 rounded-xl transition text-sm font-medium {{ request()->is('supplier*') ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30' : 'hover:bg-slate-800 hover:text-white text-slate-400' }}">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8h4.586a1 1 0 01.707.293l2.414 2.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1m-4 0a1 1 0 01-1-1"/>
                            </svg>
                            <span class="ml-3 truncate" x-show="sidebarOpen">Supplier</span>
                        </a>

                        <!-- 4. Pembelian (BARU) -->
                        <a href="{{ url('/pembelian') }}"
                           class="flex items-center px-3 py-2.5 rounded-xl transition text-sm font-medium {{ request()->is('pembelian*') ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30' : 'hover:bg-slate-800 hover:text-white text-slate-400' }}">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                            <span class="ml-3 truncate" x-show="sidebarOpen">Pembelian</span>
                        </a>

                        <!-- 5. Penjualan (POS Kasir) -->
                        <a href="{{ url('/penjualan') }}"
                           class="flex items-center px-3 py-2.5 rounded-xl transition text-sm font-medium {{ request()->is('penjualan*') ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30' : 'hover:bg-slate-800 hover:text-white text-slate-400' }}">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <span class="ml-3 truncate" x-show="sidebarOpen">Penjualan (POS)</span>
                        </a>

                        <!-- 6. Riwayat Penjualan (Ikon Diperbaiki) -->
                        <a href="{{ url('/riwayat') }}"
                           class="flex items-center px-3 py-2.5 rounded-xl transition text-sm font-medium {{ request()->is('riwayat*') ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30' : 'hover:bg-slate-800 hover:text-white text-slate-400' }}">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="ml-3 truncate" x-show="sidebarOpen">Riwayat Penjualan</span>
                        </a>

                    </nav>
                </div>

                <!-- BAGIAN BAWAH: JAM + USER INFO + BUTTON LOGOUT -->
                <div class="p-4 border-t border-slate-800 space-y-3">

                    <!-- Realtime Clock -->
                    <div x-show="sidebarOpen"
                         class="bg-slate-800/80 px-3 py-1.5 rounded-lg border border-slate-700/50 text-center text-[11px] font-mono font-semibold text-emerald-400"
                         x-data="{ time: '' }"
                         x-init="setInterval(() => time = new Date().toLocaleTimeString('id-ID'), 1000)"
                         x-text="time">
                    </div>

                    <!-- Profile Info User -->
                    @php
                        $userSession = session('user');

                        $namaUser = is_array($userSession)
                            ? ($userSession['name'] ?? $userSession['username'] ?? 'Supervisor')
                            : ($userSession->name ?? $userSession->username ?? 'Supervisor');

                        $roleUser = is_array($userSession)
                            ? ($userSession['role'] ?? 'Supervisor')
                            : ($userSession->role ?? 'Supervisor');

                        $inisial = strtoupper(substr($namaUser, 0, 1));
                    @endphp

                    <div class="flex items-center space-x-3">
                        <div class="w-9 h-9 rounded-full bg-emerald-600 flex items-center justify-center font-bold text-white text-sm shrink-0">
                            {{ $inisial }}
                        </div>
                        <div class="truncate" x-show="sidebarOpen">
                            <p class="text-xs font-semibold text-white truncate">
                                {{ $namaUser }}
                            </p>
                            <p class="text-[10px] text-slate-400 truncate">
                                {{ $roleUser }}
                            </p>
                        </div>
                    </div>

                    <!-- Button Action Logout -->
                    <div x-show="sidebarOpen">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-center text-xs font-semibold text-red-400 hover:text-red-300 bg-red-950/40 hover:bg-red-900/50 py-2 rounded-lg transition border border-red-800/40">
                                Keluar
                            </button>
                        </form>
                    </div>

                </div>
            </div>
        </aside>

        <!-- ================= MAIN CONTENT AREA ================= -->
        <main class="flex-1 overflow-y-auto p-6 min-w-0">
            @yield('content')
        </main>

    </div>

    @stack('scripts')
</body>
</html>

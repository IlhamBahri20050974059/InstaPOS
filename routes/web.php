<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PenjualanController; // <-- Import PenjualanController
use App\Http\Middleware\CheckAuth;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\RiwayatPenjualanController;

// Guest Routes (Bisa diakses tanpa login)
Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');

// Protected Routes (Wajib Login dulu)
Route::middleware([CheckAuth::class])->group(function () {

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

   Route::get('/penjualan', [PenjualanController::class, 'index'])->name('penjualan.index');

    // Fetch Data Produk & Pelanggan untuk Kasir
    Route::get('/penjualan/produk', [PenjualanController::class, 'getProducts'])->name('penjualan.produk');
    Route::get('/penjualan/pelanggan', [PenjualanController::class, 'getCustomers'])->name('penjualan.pelanggan');

    // Submit Transaksi Penjualan ke Server API
    Route::post('/penjualan', [PenjualanController::class, 'store'])->name('penjualan.store');

    // Cetak Struk PDF
    Route::get('/penjualan/{id}/cetak-pdf', [PenjualanController::class, 'cetakPdf'])->name('penjualan.cetak-pdf');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');


Route::prefix('produk')->name('products.')->group(function () {
    Route::get('/', [ProductController::class, 'index'])->name('index');
    Route::get('/data', [ProductController::class, 'getProducts'])->name('data');
    Route::get('/categories', [ProductController::class, 'getCategories'])->name('categories');
    Route::post('/', [ProductController::class, 'store'])->name('store');
    Route::patch('/{id}', [ProductController::class, 'update'])->name('update');
});


Route::prefix('supplier')->name('suppliers.')->group(function () {
    Route::get('/', [SupplierController::class, 'index'])->name('index');
    Route::get('/data', [SupplierController::class, 'getSuppliers'])->name('data');
    Route::post('/', [SupplierController::class, 'store'])->name('store');
    Route::patch('/{id}', [SupplierController::class, 'update'])->name('update');
});



    // Halaman Utama Riwayat Penjualan
    Route::get('/riwayat', [RiwayatPenjualanController::class, 'index'])->name('riwayat-penjualan.index');

    // Endpoint Fetch Data (Support Filter Tanggal & Pencarian)
    Route::get('/riwayat/data', [RiwayatPenjualanController::class, 'data'])->name('riwayat-penjualan.data');

    // Endpoint Cetak Struk PDF
    Route::get('/riwayat/{id}/cetak-pdf', [RiwayatPenjualanController::class, 'cetakPdf'])->name('riwayat-penjualan.cetak-pdf');


    // Halaman Utama Kategori
    Route::get('/kategori', [KategoriController::class, 'index'])->name('kategori.index');

    // Endpoint API Web untuk Alpine.js
    Route::get('/kategori/data', [KategoriController::class, 'data'])->name('kategori.data');
    Route::post('/kategori', [KategoriController::class, 'store'])->name('kategori.store');
    Route::patch('/kategori/{id}', [KategoriController::class, 'update'])->name('kategori.update');

});

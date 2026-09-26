<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PenjualanController; // <-- Import PenjualanController
use App\Http\Middleware\CheckAuth;

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

    // Route Penjualan POS
    Route::get('/penjualan', [PenjualanController::class, 'index'])->name('penjualan.index');

    // Route Proxy Internal API Produk
    Route::get('/penjualan/get-products', [PenjualanController::class, 'getProducts'])->name('penjualan.get-products');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

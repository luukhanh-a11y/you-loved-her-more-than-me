<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\CheckAdmin;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Login\LoginController;
use App\Http\Controllers\Product\ProductController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Payment\MoMoController;
use App\Http\Controllers\CartController;

// Trang chủ
Route::get('/', [HomeController::class, 'index'])->name('home');

// Authentication routes
Route::get('/login', [LoginController::class, 'showLoginRegister'])->name('login');
Route::post('/register', [LoginController::class, 'register'])->name('register');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Google OAuth routes
Route::get('auth/google', [GoogleController::class, 'redirectToGoogle'])->name('google.login');
Route::get('auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);

// Admin routes
Route::middleware(['auth', CheckAdmin::class])->prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::resource('products', ProductController::class);
});

// Product routes
Route::get('/danh-muc/{slug}', [ProductController::class, 'getByCategory'])->name('shop.category');
Route::get('/san-pham', [ProductController::class, 'index'])->name('shop.index');
Route::get('/san-pham/{slug}.html', [ProductController::class, 'detail'])->name('shop.detail');
Route::get('/hot-sale', [ProductController::class, 'hotSale'])->name('shop.hotSale');

// Brand routes
Route::get('/thuong-hieu', [BrandController::class, 'index'])->name('brands.index');
Route::get('/thuong-hieu/{slug}', [BrandController::class, 'show'])->name('brands.show');

// Payment routes - MoMo
Route::middleware(['auth'])->group(function () {
    // Trang checkout
    Route::get('/thanh-toan', [MoMoController::class, 'showCheckout'])->name('payment.checkout');
    
    // Tạo thanh toán MoMo
    Route::post('/payment/momo/create', [MoMoController::class, 'createPayment'])->name('momo.create');
    
    // Kiểm tra trạng thái đơn hàng
    Route::get('/payment/order/{orderId}/status', [MoMoController::class, 'checkStatus'])->name('momo.check.status');
});

// Callback từ MoMo (không cần auth vì user redirect từ MoMo)
Route::get('/payment/momo/callback', [MoMoController::class, 'callback'])->name('momo.callback');

// IPN từ MoMo (webhook - không cần auth)
Route::post('/payment/momo/ipn', [MoMoController::class, 'ipn'])->name('momo.ipn');

// Other pages
Route::get('/return-policy', function () {
    return view('user.return_policy');
})->name('return.policy');
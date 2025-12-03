<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\CheckAdmin;

// ============= CONTROLLERS CHO USER =============
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Login\LoginController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Product\CartController;
use App\Http\Controllers\Product\ProductController as UserProductController;
use App\Http\Controllers\BrandController as UserBrandController;
use App\Http\Controllers\Payment\MoMoController;

// ============= CONTROLLERS CHO ADMIN =============
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\BrandController as AdminBrandController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductVariantController;

/*
|--------------------------------------------------------------------------
| ROUTES CHO USER (KHÁCH HÀNG)
|--------------------------------------------------------------------------
*/

// Trang chủ
Route::get('/', [HomeController::class, 'index'])->name('home');

// Authentication routes
Route::get('/login', [LoginController::class, 'showLoginRegister'])->name('login');
Route::post('/register', [LoginController::class, 'register'])->name('register');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
// Cart routes (USER)
Route::middleware(['auth'])->group(function () {
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
    Route::post('/cart/remove/{id}', [CartController::class, 'remove'])->name('cart.remove');
    Route::post('/cart/update/{id}', [CartController::class, 'update'])->name('cart.update');
    Route::post('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');
});

// Google OAuth routes
Route::get('auth/google', [GoogleController::class, 'redirectToGoogle'])->name('google.login');
Route::get('auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);

// Product routes (USER)
Route::get('/danh-muc/{slug}', [UserProductController::class, 'getByCategory'])->name('shop.category');
Route::get('/san-pham', [UserProductController::class, 'index'])->name('shop.index');
Route::get('/san-pham/{slug}.html', [UserProductController::class, 'detail'])->name('shop.detail');
Route::get('/hot-sale', [UserProductController::class, 'hotSale'])->name('shop.hotSale');

// Brand routes (USER)
Route::get('/thuong-hieu', [UserBrandController::class, 'index'])->name('brands.index');
Route::get('/thuong-hieu/{slug}', [UserBrandController::class, 'show'])->name('brands.show');

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

/*
|--------------------------------------------------------------------------
| ROUTES CHO ADMIN
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', CheckAdmin::class])->prefix('admin')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    
    // Trang quản lý chung
    Route::get('/inventory', [InventoryController::class, 'index'])->name('admin.inventory');

    // CRUD cho Products (ADMIN)
    Route::resource('products', AdminProductController::class);
    
    // CRUD cho Categories (ADMIN)
    Route::resource('categories', CategoryController::class);
    
    // CRUD cho Brands (ADMIN)
    Route::resource('brands', AdminBrandController::class);

    // Product Variants (ADMIN)
    Route::post('products/{product}/variants', [ProductVariantController::class, 'store'])->name('product_variants.store');
    Route::delete('variants/{variant}', [ProductVariantController::class, 'destroy'])->name('product_variants.destroy');
    Route::get('variants', [ProductVariantController::class, 'index'])->name('product_variants.index');
});
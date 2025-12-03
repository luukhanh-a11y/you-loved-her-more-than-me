<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\CheckAdmin;

// ============= CONTROLLERS CHO USER =============
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Login\LoginController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Product\ProductController as UserProductController;
use App\Http\Controllers\BrandController as UserBrandController;
use App\Http\Controllers\Payment\MoMoController;
use App\Http\Controllers\CartController;

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

// ============================================================
// [QUAN TRỌNG] ROUTES GIỎ HÀNG (CART)
// ============================================================
Route::middleware(['auth'])->group(function () {
    Route::get('/gio-hang', [CartController::class, 'index'])->name('cart.index');
    Route::post('/add-to-cart', [CartController::class, 'addToCart'])->name('cart.add');
    Route::get('/remove-from-cart/{id}', [CartController::class, 'remove'])->name('cart.remove');
    Route::post('/update-cart', [CartController::class, 'updateCart'])->name('cart.update'); // Cần cho AJAX update số lượng
    Route::get('/clear-cart', [CartController::class, 'clear'])->name('cart.clear');
});

// ============================================================
// ROUTES THANH TOÁN (MOMO)
// ============================================================
Route::middleware(['auth'])->group(function () {
    // Trang checkout (Điền thông tin giao hàng)
    Route::get('/thanh-toan', [MoMoController::class, 'showCheckout'])->name('payment.checkout');
    
    // Xử lý tạo thanh toán gửi sang MoMo
    Route::post('/payment/momo/create', [MoMoController::class, 'createPayment'])->name('momo.create');
    
    // Kiểm tra trạng thái (Optional)
    Route::get('/payment/order/{orderId}/status', [MoMoController::class, 'checkStatus'])->name('momo.check.status');
});

// Callback từ MoMo trả về (Quan trọng: route này MoMo gọi lại user sau khi thanh toán)
Route::get('/payment/momo/callback', [MoMoController::class, 'callback'])->name('momo.callback');

// IPN từ MoMo (Webhook server-to-server, không cần auth)
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

    // CRUD
    Route::resource('products', AdminProductController::class);
    Route::resource('categories', CategoryController::class);
    Route::resource('brands', AdminBrandController::class);

    // Product Variants
    Route::post('products/{product}/variants', [ProductVariantController::class, 'store'])->name('product_variants.store');
    Route::delete('variants/{variant}', [ProductVariantController::class, 'destroy'])->name('product_variants.destroy');
    Route::get('variants', [ProductVariantController::class, 'index'])->name('product_variants.index');
});
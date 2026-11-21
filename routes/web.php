<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CashbookController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeployController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

// Public landing page
Route::get('/', function () {
    return view('welcome');
})->name('landing');

// Secured deploy hook (no shell on shared hosting): POST with X-Deploy-Token
Route::post('/deploy/migrate', [DeployController::class, 'migrate'])->name('deploy.migrate');

// Auth (guests)
Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/forgot-password', [AuthController::class, 'showForgot'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'updatePassword'])->name('password.update');
});

// Authenticated app
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
    Route::get('/sales/create', [SaleController::class, 'create'])->name('sales.create');
    Route::get('/sales/{id}', [SaleController::class, 'show'])->name('sales.show');

    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/{id}', [CustomerController::class, 'show'])->name('customers.show');

    Route::get('/purchases', [PurchaseController::class, 'index'])->name('purchases.index');
    Route::get('/purchases/create', [PurchaseController::class, 'create'])->name('purchases.create');
    Route::get('/purchases/{id}', [PurchaseController::class, 'show'])->name('purchases.show');

    Route::get('/suppliers/{id}', [SupplierController::class, 'show'])->name('suppliers.show');

    Route::get('/products', [ProductController::class, 'index'])->name('products.index');

    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');

    Route::get('/cashbook', [CashbookController::class, 'index'])->name('cashbook.index');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/password', [SettingsController::class, 'changePassword'])->name('settings.password');
});

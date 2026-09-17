<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ListingPublicController;
use App\Http\Controllers\Buyer\CartController;
use App\Http\Controllers\Buyer\CheckoutController;
use App\Http\Controllers\Buyer\OrderController;
use App\Http\Controllers\Buyer\ReviewController;
use App\Http\Controllers\Seller\ListingController as SellerListingController;
use App\Http\Controllers\Seller\SaleController as SellerSaleController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\ProfileController;

/*
|--------------------------------------------------------------------------
| Rotas Públicas (Catálogo e Detalhes)
|--------------------------------------------------------------------------
*/
Route::get('/', [ListingPublicController::class, 'home'])->name('home');
Route::get('/anuncios', [ListingPublicController::class, 'index'])->name('listings.index');
Route::get('/anuncios/{slug}', [ListingPublicController::class, 'show'])->name('listings.show');

/*
|--------------------------------------------------------------------------
| Autenticação e Sessão
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/cadastro', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/cadastro', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Rotas do Comprador (Autenticadas)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    // Carrinho
    Route::get('/carrinho', [CartController::class, 'index'])->name('cart.index');
    Route::post('/carrinho/adicionar/{listing}', [CartController::class, 'add'])->name('cart.add');
    Route::delete('/carrinho/remover/{item}', [CartController::class, 'remove'])->name('cart.remove');
    Route::delete('/carrinho/limpar', [CartController::class, 'clear'])->name('cart.clear');

    // Checkout
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout/processar', [CheckoutController::class, 'process'])->name('checkout.process');
    Route::get('/checkout/sucesso/{order}', [CheckoutController::class, 'success'])->name('checkout.success');
    Route::get('/checkout/cancelado/{order}', [CheckoutController::class, 'cancel'])->name('checkout.cancel');

    // Histórico de Pedidos & Avaliação
    Route::get('/pedidos', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/pedidos/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/pedidos/{order}/avaliar/{item}', [ReviewController::class, 'store'])->name('orders.review.store');
    Route::post('/denunciar/anuncio/{listing}', [ListingPublicController::class, 'report'])->name('listings.report');

    // Perfil do Usuário e Troca de Senha
    Route::get('/perfil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/perfil', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/perfil/senha', [ProfileController::class, 'updatePassword'])->name('profile.password');
});

/*
|--------------------------------------------------------------------------
| Rotas do Vendedor (Perfil Vendedor ou Admin)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->prefix('vendedor')->name('seller.')->group(function () {
    Route::resource('anuncios', SellerListingController::class);
    Route::patch('anuncios/{listing}/status', [SellerListingController::class, 'toggleStatus'])->name('anuncios.status');
    Route::get('vendas', [SellerSaleController::class, 'index'])->name('sales.index');
    Route::patch('vendas/{item}/entregar', [SellerSaleController::class, 'markAsDelivered'])->name('sales.deliver');
});

/*
|--------------------------------------------------------------------------
| Rotas Administrativas (Apenas Admin)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::resource('categorias', AdminCategoryController::class);
    Route::resource('usuarios', AdminUserController::class);
    Route::get('denuncias', [AdminReportController::class, 'index'])->name('reports.index');
    Route::patch('denuncias/{report}/moderar', [AdminReportController::class, 'moderate'])->name('reports.moderate');
});

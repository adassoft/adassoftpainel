<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShopController;



Route::get('/', [ShopController::class, 'index'])->name('home');
Route::get('/produto/{id}', [\App\Http\Controllers\ProductController::class, 'show'])->name('product.show');
Route::get('/downloads', [\App\Http\Controllers\DownloadController::class, 'index'])->name('downloads');
Route::get('/download/{id}', [\App\Http\Controllers\DownloadController::class, 'show'])->name('download.show');
Route::get('/seja-parceiro', [\App\Http\Controllers\ResellerController::class, 'index'])->name('reseller.lp');
Route::get('/revenda/cadastro', [\App\Http\Controllers\ResellerController::class, 'register'])->name('reseller.register');
Route::post('/revenda/cadastro', [\App\Http\Controllers\ResellerController::class, 'store'])->name('reseller.store');

// Checkout routes public (auth handled inside)
Route::get('/checkout/{planId}', [\App\Http\Controllers\CheckoutController::class, 'start'])->name('checkout.start');
Route::post('/checkout/{planId}/pix', [\App\Http\Controllers\CheckoutController::class, 'processPix'])->name('checkout.pix');
Route::post('/checkout/auth', [\App\Http\Controllers\CheckoutController::class, 'authenticate'])->name('checkout.auth');

Route::middleware(['auth'])->group(function () {
    Route::get('/sys/force-db-update', function () {
        // Verificação de administrador removida temporariamente para correção

        try {
            \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            return '<h1>Banco de Dados Atualizado com Sucesso!</h1><pre>' . \Illuminate\Support\Facades\Artisan::output() . '</pre> <br> <a href="/admin">Voltar para o Painel</a>';
        } catch (\Exception $e) {
            return '<h1>Erro ao Atualizar</h1><pre>' . $e->getMessage() . '</pre>';
        }
    });
});

// Correção para redirecionamento de Auth (Cliente Final)
Route::get('/login', function () {
    return redirect()->route('filament.app.auth.login');
})->name('login');

// Rota de fallback para imagens antigas se necessário (opcional)
// Route::get('/img/{path}', ...)
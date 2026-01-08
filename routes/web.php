<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShopController;
use App\Models\Redirect;

Route::get('/', [ShopController::class, 'index'])->name('home');
Route::get('/produto/{id}', [\App\Http\Controllers\ProductController::class, 'show'])->name('product.show');
Route::get('/downloads', [\App\Http\Controllers\DownloadController::class, 'index'])->name('downloads');
Route::get('/downloads/{id}', [\App\Http\Controllers\DownloadController::class, 'show'])->name('downloads.show');
Route::get('/downloads/{id}/baixar', [\App\Http\Controllers\DownloadController::class, 'downloadFile'])->name('downloads.file');
Route::get('/downloads/version/{id}', [\App\Http\Controllers\DownloadController::class, 'downloadVersion'])->name('downloads.version.file');
Route::get('/seja-parceiro', [\App\Http\Controllers\ResellerController::class, 'index'])->name('reseller.lp');
Route::get('/revenda/cadastro', [\App\Http\Controllers\ResellerController::class, 'register'])->name('reseller.register');
Route::post('/revenda/cadastro', [\App\Http\Controllers\ResellerController::class, 'store'])->name('reseller.store');

Route::get('/parceiros', function () {
    return view('shop.partners-selector');
})->name('partners.index');

// Rota para Desenvolvedores (Landing Page)
Route::get('/dev', [\App\Http\Controllers\DeveloperPartnerController::class, 'index'])->name('developer.lp');
Route::post('/dev/join', [\App\Http\Controllers\DeveloperPartnerController::class, 'store'])->name('developer.store');

Route::get('/feeds/google.xml', [\App\Http\Controllers\FeedController::class, 'google'])->name('feeds.google');

// Checkout routes public (auth handled inside)
Route::get('/checkout/{planId}', [\App\Http\Controllers\CheckoutController::class, 'start'])->name('checkout.start');
Route::post('/checkout/{planId}/pix', [\App\Http\Controllers\CheckoutController::class, 'processPix'])->name('checkout.pix');
Route::get('/checkout/download/{id}', [\App\Http\Controllers\CheckoutController::class, 'startDownload'])->name('checkout.download.start');
Route::post('/checkout/download/{id}/process', [\App\Http\Controllers\CheckoutController::class, 'processDownloadPix'])->name('checkout.download.process');
Route::post('/checkout/auth', [\App\Http\Controllers\CheckoutController::class, 'authenticate'])->name('checkout.auth');

// Rota de Polling para verificar pagamento PIX (AJAX)
Route::get('/checkout/status/{externalRef}', function ($externalRef) {
    if (!auth()->check())
        return response()->json(['status' => 'error'], 401);

    $order = \App\Models\Order::where('external_reference', $externalRef)
        ->orWhere('asaas_payment_id', $externalRef)
        ->orWhere('external_id', $externalRef) // Caso use external_id do legado
        ->first();

    if (!$order) {
        return response()->json(['status' => 'not_found'], 404);
    }

    $isPaid = in_array(strtoupper($order->status), ['PAID', 'PAGO', 'COMPLETED', 'RECEIVED']);

    // Determina URL de redirecionamento
    $redirect = route('home');
    if ($order->items->count() > 0) {
        $item = $order->items->first();
        // Tenta pegar o slug ou o id do download
        $dl = \App\Models\Download::find($item->download_id);
        if ($dl) {
            $redirect = route('downloads.show', $dl->slug ?? $dl->id);
        }
    } elseif ($order->plano_id) {
        // Se for plano/assinatura
        $redirect = route('filament.app.pages.dashboard'); // Redireciona para o painel
    } elseif ($order->recorrencia === 'CREDITO') {
        // Se for recarga de credito
        $redirect = route('filament.reseller.pages.my-wallet');
    }

    return response()->json([
        'status' => $order->status,
        'paid' => $isPaid,
        'redirect_url' => $redirect
    ]);
})->name('checkout.check_status');

Route::middleware(['auth'])->group(function () {
    /*
    // Rota de Emergência (Desativada por Segurança)
    Route::get('/sys/force-db-update', function () {
        // Verificação de administrador removida temporariamente para correção

        try {
            \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            return '<h1>Banco de Dados Atualizado com Sucesso!</h1><pre>' . \Illuminate\Support\Facades\Artisan::output() . '</pre> <br> <a href="/admin">Voltar para o Painel</a>';
        } catch (\Exception $e) {
            return '<h1>Erro ao Atualizar</h1><pre>' . $e->getMessage() . '</pre>';
        }
    });
    */
});

// Correção para redirecionamento de Auth (Cliente Final)
Route::get('/login', function () {
    return redirect()->route('filament.app.auth.login');
})->name('login');

Route::get('/legal/{slug}', [\App\Http\Controllers\LegalPageController::class, 'show'])->name('legal.show');

// KB Pública
Route::get('/ajuda', [\App\Http\Controllers\KbController::class, 'index'])->name('kb.index');
Route::get('/ajuda/categoria/{slug}', [\App\Http\Controllers\KbController::class, 'category'])->name('kb.category');
Route::get('/ajuda/{slug}', [\App\Http\Controllers\KbController::class, 'show'])->name('kb.show');

// === Gerenciador de Redirecionamentos (SEO) ===
Route::fallback(function () {
    $path = request()->path();
    // Garante que o path comece com / se não tiver (request()->path() retorna 'foo/bar')
    $pathWithSlash = '/' . $path;

    // Tenta encontrar o redirect (cachear isso seria bom em produção com alto tráfego)
    $redirect = Redirect::where(function ($query) use ($path, $pathWithSlash) {
        $query->where('path', $path)
            ->orWhere('path', $pathWithSlash);
    })->where('is_active', true)->first();

    if ($redirect) {
        return redirect($redirect->target_url, $redirect->status_code);
    }

    // Se quiser, descomente abaixo para enviar qualquer 404 para o blog
    // return redirect('https://blog.adassoft.com/' . $path);

    abort(404);
});
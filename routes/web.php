<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShopController;
use App\Models\Redirect;

Route::get('/', [ShopController::class, 'index'])->name('home');
Route::get('/produto/{id}', [\App\Http\Controllers\ProductController::class, 'show'])->name('product.show');
Route::get('/downloads', [\App\Http\Controllers\DownloadController::class, 'index'])->name('downloads');
Route::get('/downloads/{id}', [\App\Http\Controllers\DownloadController::class, 'show'])->name('downloads.show');
Route::get('/downloads/{id}/baixar', [
    \App\Http\Controllers\DownloadController::class,
    'downloadFile'
])->name('downloads.file');
Route::get('/downloads/version/{id}', [
    \App\Http\Controllers\DownloadController::class,
    'downloadVersion'
])->name('downloads.version.file');
Route::get('/seja-parceiro', [\App\Http\Controllers\ResellerController::class, 'index'])->name('reseller.lp');
Route::get('/revenda/cadastro', [
    \App\Http\Controllers\ResellerController::class,
    'register'
])->name('reseller.register');
Route::post('/revenda/cadastro', [\App\Http\Controllers\ResellerController::class, 'store'])->name('reseller.store');

Route::get('/parceiros', function () {
    return view('shop.partners-selector');
})->name('partners.index');

// Rota para Desenvolvedores (Landing Page)
Route::get('/dev', [\App\Http\Controllers\DeveloperPartnerController::class, 'index'])->name('developer.lp');
Route::post('/dev/join', [\App\Http\Controllers\DeveloperPartnerController::class, 'store'])->name('developer.store');

Route::get('/feeds/google.xml', [\App\Http\Controllers\FeedController::class, 'google'])->name('feeds.google');
Route::get('/sitemap.xml', [\App\Http\Controllers\SitemapController::class, 'index'])->name('sitemap');

// Checkout routes public (auth handled inside)
Route::get('/checkout/{planId}', [\App\Http\Controllers\CheckoutController::class, 'start'])->name('checkout.start');
Route::post('/checkout/{planId}/pix', [
    \App\Http\Controllers\CheckoutController::class,
    'processPix'
])->name('checkout.pix');
Route::get('/checkout/download/{id}', [
    \App\Http\Controllers\CheckoutController::class,
    'startDownload'
])->name('checkout.download.start');
Route::post('/checkout/download/{id}/process', [
    \App\Http\Controllers\CheckoutController::class,
    'processDownloadPix'
])->name('checkout.download.process');
Route::post('/checkout/auth', [\App\Http\Controllers\CheckoutController::class, 'authenticate'])->name('checkout.auth');

Route::get('/checkout/success/{order}', function (\App\Models\Order $order) {
    if (!auth()->check() || auth()->id() !== $order->user_id) {
        return redirect()->route('login');
    }

    // Determina URL final (Painel ou Download)
    $redirectUrl = route('filament.app.pages.dashboard');
    if ($order->items->count() > 0) {
        $item = $order->items->first();
        $dl = \App\Models\Download::find($item->download_id);
        if ($dl) {
            $redirectUrl = route('downloads.show', $dl->slug ?? $dl->id);
        }
    } elseif ($order->recorrencia === 'CREDITO') {
        $redirectUrl = route('filament.reseller.pages.my-wallet');
    }

    return view('checkout.success', compact('order', 'redirectUrl'));
})->name('checkout.success');

// Rota de Polling para verificar pagamento PIX (AJAX)
Route::get('/checkout/status/{externalRef}', function ($externalRef) {
    if (!auth()->check())
        return response()->json(['status' => 'error'], 401);

    $order = \App\Models\Order::where('external_reference', $externalRef)
        ->orWhere('asaas_payment_id', $externalRef)
        ->orWhere('external_id', $externalRef)
        ->first();

    if (!$order) {
        return response()->json(['status' => 'not_found'], 404);
    }

    $isPaid = in_array(strtoupper($order->status), ['PAID', 'PAGO', 'COMPLETED', 'RECEIVED']) ||
        in_array(strtoupper($order->situacao ?? ''), ['PAGO', 'APROVADO']);

    // Agora redireciona sempre para a Success Page para disparar o Pixel
    $redirect = route('checkout.success', $order->id);

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
    return '<h1>Banco de Dados Atualizado com Sucesso!</h1>
    <pre>' . \Illuminate\Support\Facades\Artisan::output() . '</pre> <br> <a href="/admin">Voltar para o Painel</a>';
    } catch (\Exception $e) {
    return '<h1>Erro ao Atualizar</h1>
    <pre>' . $e->getMessage() . '</pre>';
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
Route::post('/ajuda/{id}/vote', [\App\Http\Controllers\KbController::class, 'vote'])->name('kb.vote');

// === Mercado Livre Integration ===
Route::get('/ml/auth', [\App\Http\Controllers\MercadoLibreController::class, 'auth'])->name('ml.auth');
Route::get('/ml/callback', [\App\Http\Controllers\MercadoLibreController::class, 'callback'])->name('ml.callback');
Route::post('/ml/notifications', [\App\Http\Controllers\MercadoLibreController::class, 'webhook'])->name('ml.webhook');

Route::middleware(['auth'])->group(function () {
    Route::post('/tinymce/upload', [
        \App\Http\Controllers\TinyMceUploadController::class,
        'upload'
    ])->name('tinymce.upload');
});



// Solicitacao de Software
Route::get('/software-sob-medida', [\App\Http\Controllers\SoftwareRequestController::class, 'index'])->name('software-request.index');
Route::post('/software-sob-medida', [\App\Http\Controllers\SoftwareRequestController::class, 'store'])->name('software-request.store');

// === Download Lead Capture ===
Route::post('/downloads/lead', [\App\Http\Controllers\DownloadController::class, 'storeLead'])->name('downloads.lead.store');
Route::get('/downloads/{id}/secure-link', [\App\Http\Controllers\DownloadController::class, 'downloadFileSigned'])->name('downloads.file.signed')->middleware('signed');
Route::get('/downloads/version/{id}/secure-link', [\App\Http\Controllers\DownloadController::class, 'downloadVersionSigned'])->name('downloads.version.signed')->middleware('signed');

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

    // Fallback: Se não existe no sistema, redireciona 301 para o Blog (Legado WP)

    // Limpeza de URLs legadas (AMP)
    // Remove /amp do final para redirecionar para o post original limpo
    if (str_ends_with($path, '/amp')) {
        $path = substr($path, 0, -4);
    }

    // Isso resolve os erros de rastreamento de URLs antigas
    return redirect()->away('https://blog.adassoft.com/' . $path, 301);
});
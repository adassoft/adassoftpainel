<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ValidationController;
use App\Http\Controllers\Api\AsaasWebhookController;
use App\Http\Controllers\Api\ResellerWebhookController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/news', [\App\Http\Controllers\Api\NewsController::class, 'index']);
});

// Webhooks
Route::post('/webhooks/asaas', [App\Http\Controllers\Api\AsaasWebhookController::class, 'handle']);
Route::post('/webhooks/reseller/asaas', [App\Http\Controllers\Api\ResellerWebhookController::class, 'handle']);

// === API AdasSoft V1 (RESTful) ===
Route::prefix('v1/adassoft')->middleware(['throttle:60,1', 'shield.auth'])->group(function () {
    // Compatibilidade Legado (action=...)
    Route::match(['get', 'post'], '/', [ValidationController::class, 'handle']);

    // Validação e Status
    Route::post('/validate', [ValidationController::class, 'handle']); // Mantém handle por enquanto ou refatora para 'validateSerial'
    // Ações Sensíveis (Login/Cadastro) - Limite Rígido anti-bruteforce
    Route::post('/token', [ValidationController::class, 'handle'])
        ->middleware('throttle:6,1'); // Max 6 tentativas de login/min

    Route::post('/register', [ValidationController::class, 'registerUser'])
        ->middleware('throttle:6,1'); // Max 6 cadastros/min

    // Cadastros e Pedidos (Geral)
    Route::get('/software/{software_id}/plans', [ValidationController::class, 'listPlans']);
    Route::post('/orders', [ValidationController::class, 'createOrder']);
    Route::post('/orders/status', [ValidationController::class, 'checkPaymentStatus']);

    // Notícias (Endpoint Dedicado)
    Route::get('/news', [ValidationController::class, 'fetchNews']);

    // Updates Automáticos (SDK)
    Route::get('/updates/check', [\App\Http\Controllers\Api\UpdateController::class, 'check']);

    // Rotas legadas (removidas pois estamos começando do zero)
});

// Download Seguro de Updates (URL Assinada - Válida por X minutos)
Route::prefix('v1/adassoft')->group(function () {
    Route::get('/updates/download/{versionId}', [\App\Http\Controllers\Api\UpdateController::class, 'download'])
        ->name('api.updates.download')
        ->middleware('signed');
});

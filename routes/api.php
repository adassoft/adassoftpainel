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
    // Validação e Status
    Route::post('/validate', [ValidationController::class, 'handle']); // Mantém handle por enquanto ou refatora para 'validateSerial'
    Route::post('/token', [ValidationController::class, 'handle']); // Action: emitir_token

    // Cadastros e Pedidos
    Route::get('/software/{software_id}/plans', [ValidationController::class, 'listPlans']);
    Route::post('/orders', [ValidationController::class, 'createOrder']);
    Route::post('/register', [ValidationController::class, 'registerUser']);

    // Rotas legadas (removidas pois estamos começando do zero)
});

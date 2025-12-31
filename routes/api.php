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

// Rota de compatibilidade legado
Route::any('/validacao', [ValidationController::class, 'handle']);

// Webhooks
Route::post('/webhooks/asaas', [AsaasWebhookController::class, 'handle']); // Legacy
Route::post('/webhooks/reseller/asaas', [ResellerWebhookController::class, 'handle']); // New Reseller Flow

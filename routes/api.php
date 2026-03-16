<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CommunicationWebhookController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware(['auth:sanctum', 'verified']);

// Communication webhook routes (no authentication required)
Route::prefix('webhooks/communication')->group(function () {
    Route::post('/twilio', [CommunicationWebhookController::class, 'twilio'])
        ->name('webhooks.communication.twilio');

    Route::post('/africas-talking', [CommunicationWebhookController::class, 'africasTalking'])
        ->name('webhooks.communication.africas-talking');

    Route::match(['GET', 'POST'], '/meta-whatsapp', [CommunicationWebhookController::class, 'metaWhatsApp'])
        ->name('webhooks.communication.meta-whatsapp');
});

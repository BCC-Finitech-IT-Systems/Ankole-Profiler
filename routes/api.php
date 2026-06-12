<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CommunicationWebhookController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware(['auth:sanctum', 'verified']);

// Communication webhook routes (authenticated by provider signature/token)
Route::prefix('webhooks/communication')->group(function () {
    Route::post('/twilio', [CommunicationWebhookController::class, 'twilio'])
        ->middleware('webhook.verify:twilio')
        ->name('webhooks.communication.twilio');

    Route::post('/africas-talking', [CommunicationWebhookController::class, 'africasTalking'])
        ->middleware('webhook.verify:africastalking')
        ->name('webhooks.communication.africas-talking');

    Route::match(['GET', 'POST'], '/meta-whatsapp', [CommunicationWebhookController::class, 'metaWhatsApp'])
        ->middleware('webhook.verify:meta')
        ->name('webhooks.communication.meta-whatsapp');
});

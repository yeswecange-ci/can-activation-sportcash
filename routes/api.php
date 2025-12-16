<?php

use App\Http\Controllers\Api\TwilioStudioController;
use App\Http\Controllers\Api\TwilioWebhookController;
use App\Http\Controllers\Api\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Webhooks WhatsApp/Twilio (pas d'authentification requise)
Route::post('/webhook/whatsapp', [WhatsAppWebhookController::class, 'receiveMessage'])
    ->name('api.whatsapp.webhook');

Route::post('/webhook/whatsapp/status', [WhatsAppWebhookController::class, 'statusCallback'])
    ->name('api.whatsapp.status');

// Webhooks Twilio pour les campagnes
Route::post('/webhook/twilio/status', [TwilioWebhookController::class, 'statusCallback'])
    ->name('api.twilio.status-callback');

Route::post('/webhook/twilio/incoming', [TwilioWebhookController::class, 'incomingMessage'])
    ->name('api.twilio.incoming');

// Endpoints pour Twilio Studio Flow CAN 2025
Route::prefix('can')->group(function () {
    // Tracking & Inscription
    Route::post('/scan', [TwilioStudioController::class, 'scan'])->name('api.can.scan');
    Route::post('/optin', [TwilioStudioController::class, 'optin'])->name('api.can.optin');
    Route::post('/inscription', [TwilioStudioController::class, 'inscription'])->name('api.can.inscription');
    Route::post('/refus', [TwilioStudioController::class, 'refus'])->name('api.can.refus');
    Route::post('/stop', [TwilioStudioController::class, 'stop'])->name('api.can.stop');
    Route::post('/abandon', [TwilioStudioController::class, 'abandon'])->name('api.can.abandon');
    Route::post('/timeout', [TwilioStudioController::class, 'timeout'])->name('api.can.timeout');
    Route::post('/error', [TwilioStudioController::class, 'error'])->name('api.can.error');
    Route::post('/can/reactivate', [TwilioStudioController::class, 'reactivate']);
    Route::post('/can/log', [TwilioStudioController::class, 'log']);

    // Nouvelles API pour le flow interactif
    Route::post('/check-user', [TwilioStudioController::class, 'checkUser'])->name('api.can.check-user');
    Route::get('/villages', [TwilioStudioController::class, 'getVillages'])->name('api.can.villages');

    // Matchs
    Route::get('/matches/today', [TwilioStudioController::class, 'getMatchesToday'])->name('api.can.matches.today');
    Route::get('/matches/upcoming', [TwilioStudioController::class, 'getUpcomingMatches'])->name('api.can.matches.upcoming');
    Route::get('/matches/formatted', [TwilioStudioController::class, 'getMatchesFormatted'])->name('api.can.matches.formatted');
    Route::get('/matches/{id}', [TwilioStudioController::class, 'getMatch'])->name('api.can.matches.show');

    // Pronostics et autres
    Route::post('/pronostic', [TwilioStudioController::class, 'savePronostic'])
        ->middleware('force.json')
        ->name('api.can.pronostic');
    Route::get('/pronostic/test', [TwilioStudioController::class, 'testPronostic'])->name('api.can.pronostic.test');
    Route::post('/unsubscribe', [TwilioStudioController::class, 'unsubscribe'])->name('api.can.unsubscribe');
    Route::get('/partners', [TwilioStudioController::class, 'getPartners'])->name('api.can.partners');
    Route::get('/prizes', [TwilioStudioController::class, 'getPrizes'])->name('api.can.prizes');
});

// Routes API authentifiées (pour future app mobile par exemple)
Route::middleware('auth:sanctum')->group(function () {
    // API utilisateur
    // Route::get('/user', function (Request $request) {
    //     return $request->user();
    // });
});

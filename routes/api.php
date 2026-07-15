<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CronJobController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\TwilioController;
use App\Http\Controllers\OportunityController;
use App\Http\Controllers\PvpcController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/collection', [CronJobController::class, 'collection']);

//ESIOS

Route::prefix('pvpc')->group(function () {
    Route::get('/ping', function () {
        return response()->json([
            'success' => true,
            'message' => 'Laravel llega a la API PVPC',
        ]);
    });

    Route::get('/download-test', [PvpcController::class, 'downloadTest']);
    Route::get('/today', [PvpcController::class, 'today']);
    Route::get('/tomorrow', [PvpcController::class, 'tomorrow']);
    Route::get('/date', [PvpcController::class, 'byDate']);
    Route::get('/indexed/download-test', [PvpcController::class, 'downloadIndexedTest']);
    Route::get('/indexed/date', [PvpcController::class, 'indexedByDate']);
    Route::get('/historical/weekly', [PvpcController::class, 'historicalWeekly']);
    Route::get('/historical/monthly', [PvpcController::class, 'historicalMonthly']);
    Route::get('/historical/daily', [PvpcController::class, 'historicalDaily']);
    Route::get('/historical/heatmap', [PvpcController::class, 'historicalHeatmap']);
});

Route::post('public/opportunities/generateEvChargerPDF', [OportunityController::class, 'generateEvChargerPDF']);


Route::get('/public/opportunities/{id}', [OportunityController::class, 'showPublic']);

Route::post('/public/opportunities/logEvChargerProgress', [OportunityController::class, 'logEvChargerProgress']);
Route::post('opportunities/saveActa', [OportunityController::class, 'saveActa']);


Route::get('/sendEmailOrders', [CronJobController::class, 'sendEmailOrders']);

Route::post('/webhooks/wordpress/opportunity', [WebhookController::class, 'wordpressOpportunity']);

Route::prefix('stripe')->group(function () {
    Route::post('/webhook', [StripeController::class, 'handleWebhook']);
});

Route::prefix('twilio')->group(function () {
    Route::match(['GET', 'POST'], '/voice', [TwilioController::class, 'voice']);
    Route::match(['GET', 'POST'], '/recording-callback', [TwilioController::class, 'recordingCallback']);
    Route::match(['GET', 'POST'], '/countCallMinutes', [TwilioController::class, 'countCallMinutes']);
});

<?php

use App\Helpers\ManifestVersion;
use App\Http\Controllers\HelpController;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GEOController;
use App\Http\Controllers\KeyController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SipsController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ScrapingController;
use Illuminate\Support\Facades\Response;
use App\Http\Controllers\EventController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ToolsController;
use Maatwebsite\Excel\Concerns\FromArray;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\SelectController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\TwilioController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CronJobController;
use App\Http\Controllers\GeneralController;
use App\Http\Controllers\SigningController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\MarketerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OportunityController;
use App\Http\Controllers\ComparativeController;
use App\Http\Controllers\LiquidationController;
use App\Http\Controllers\PrivacyPolicyController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\OpenComparatorController;
use App\Http\Controllers\ObjectivesController;
use App\Http\Controllers\EnterpriseController;
use App\Http\Controllers\FacebookMetaController;



/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


//secciones web
Route::prefix('api')->group(function () {
    Route::get('/meta/leads', [FacebookMetaController::class, 'verify']);
    Route::post('/meta/leads', [FacebookMetaController::class, 'receive']);

    Route::get('/help', [HelpController::class, 'help'])->middleware(['auth']);


    Route::get('/test-date', function () {
        \Carbon\Carbon::setLocale('es');
        return \Carbon\Carbon::now()->isoFormat('dddd, D [de] MMMM [de] YYYY');
    });

    Route::get('/help', [HelpController::class, 'help'])->middleware(['auth']);
    //Route::get('/help/setup-notification-email', [HelpController::class, 'setupNotificationEmail'])->middleware(['auth']);

    Route::get('/seeMail', function () {
        return view('Mail.template');
    });

    Route::prefix('auth')->group(function () {
        Route::post('/', [AuthController::class, 'auth'])->middleware(['guest']);
        Route::get('/logout', [AuthController::class, 'logout'])->middleware(['auth']);
        Route::post('/sendRecoverCode', [AuthController::class, 'sendRecoverCode'])->middleware(['guest']);
        Route::post('/checkCredentials', [AuthController::class, 'checkCredentials'])->middleware(['guest']);
        Route::put('/changePassword', [AuthController::class, 'changePassword'])->middleware(['guest']);
    });

    Route::prefix('global')->group(function () {
        Route::post('/search', [GeneralController::class, 'search'])->middleware(['auth']);
        Route::post('/updateCommissionRanges', [GeneralController::class, 'updateCommissionRanges'])->middleware(['auth']);
    });


    Route::prefix('serial')->group(function () {
        Route::post('/{id}', [KeyController::class, 'update'])->middleware(['auth']);
        Route::post('/', [KeyController::class, 'store'])->middleware(['auth']);
        Route::get('/checkKey/{key}', [KeyController::class, 'checkKey']);
    });

    Route::prefix('dashboard')->group(function () {
        Route::post('/getGeneralData', [DashboardController::class, 'getGeneralData'])->middleware(['auth']);
        Route::post('/contractsAndConsumePerDate', [DashboardController::class, 'contractsAndConsumePerDate'])->middleware(['auth']);
        Route::post('/getContractPerMarketerData', [DashboardController::class, 'getContractPerMarketerData'])->middleware(['auth']);
        Route::post('/getConsumeAndContractsPerAgentData', [DashboardController::class, 'getConsumeAndContractsPerAgentData'])->middleware(['auth']);
        Route::post('/getContractsPerStatus', [DashboardController::class, 'getContractsPerStatus'])->middleware(['auth']);
        Route::get('/getFirstDate', [DashboardController::class, 'getFirstDate'])->middleware(['auth']);
    });


    Route::prefix('scraping')->group(function () {
            Route::get('/confirmar', [ScrapingController::class, 'confirmarEntrada']);
    });

    Route::prefix('user')->group(function () {
        Route::get('/indexAll', [UserController::class, 'indexAll'])->middleware(['auth']);
        Route::post('/withKey', [UserController::class, 'storeWithKey']);
        Route::post('/createDirectly', [UserController::class, 'createDirectly'])->middleware(['auth']);
        Route::post('/update', [UserController::class, 'update'])->middleware(['auth']);
        Route::delete('/{id}', [UserController::class, 'deleteUser'])->middleware(['auth']);
        Route::post('/deleteAllSelected', [UserController::class, 'deleteAllSelectedUsers'])->middleware(['auth']);
        Route::post('/updateImage/{id}', [UserController::class, 'updateImage'])->middleware(['auth']);
        Route::post('/checkOldPassword', [UserController::class, 'checkOldPassword'])->middleware(['auth']);
        Route::put('/changePassword', [UserController::class, 'changePassword'])->middleware(['auth']);
        Route::get('/getAccountCreator', [UserController::class, 'getAccountCreator'])->middleware(['auth']);
        Route::get('/getUserHierarchy/{id}', [UserController::class, 'getUserHierarchy'])->middleware(['auth']);
        Route::post('/bulk', [UserController::class, 'bulk'])->middleware(['auth']);
        Route::post('/labelsPermissions', [UserController::class, 'saveLabelModulePermissions'])->middleware(['auth']);
        Route::post('/email/{type}', [UserController::class, 'updateEmail'])->middleware(['auth']);
        Route::post('/notification-email', [UserController::class, 'updateNotificationEmail'])->middleware(['auth']);

    });

    Route::prefix('enterprise')->group(function () {
        Route::post('/getSubscription', [EnterpriseController::class, 'getSubscription'])->middleware(['auth']);
        Route::get('/checkHasIBAN', [EnterpriseController::class, 'checkHasIBAN'])->middleware(['auth']);
    });

    Route::prefix('session')->group(function () {
        Route::get('/getData', [AuthController::class, 'getSessionData'])->middleware(['auth']);
        Route::get('/getEnterpriseData', [AuthController::class, 'getEnterpriseData']);
        Route::get('/checkUserLoggedSesion', [AuthController::class, 'checkUserLoggedSesion'])->middleware(['auth']);
        Route::get('/checkSubscriptionStatus', [AuthController::class, 'checkSubscriptionStatus'])->middleware(['auth']);
        Route::get('/getAllSuperiors/{id}', [AuthController::class, 'getAllSuperiors'])->middleware(['auth']);
    });

    Route::prefix('orders')->group(function () {
        Route::post('/', [OrderController::class, 'store'])->middleware(['auth']);
        Route::post('/create-from-invoice-quick', [OrderController::class, 'createFromInvoiceQuick'])->middleware(['auth']);
        Route::post('/saveLiquidation', [OrderController::class, 'saveLiquidation']);
        Route::get('/ordersAllowed', [OrderController::class, 'getActiveAndCommissionedContracts'])->middleware(['auth']);
        Route::post('/sendIncidenceMail', [OrderController::class, 'sendIncidenceMail'])->middleware(['auth']);
        Route::post('/index', [OrderController::class, 'index'])->middleware(['auth']);
        Route::post('/indexFilters', [OrderController::class, 'indexFilters'])->middleware(['auth']);
        Route::post('/getAccountEmails', [OrderController::class, 'getAccountEmails'])->middleware(['auth']);
        Route::delete('/{id}', [OrderController::class, 'delete'])->middleware(['auth']);
        Route::delete('/deleteAllSelected', [OrderController::class, 'deleteAllSelectedOrders'])->middleware(['auth']);
        Route::get('/getAPIConsumption', [OrderController::class, 'getAPIConsumption'])->middleware(['auth']);
        Route::get('/getAPIConsumptionYear', [OrderController::class, 'getAPIConsumptionYear'])->middleware(['auth']);
        Route::post('/dumpOrders', [OrderController::class, 'dumpOrders'])->middleware(['auth']);
        Route::post('/checkIfHasStatus', [OrderController::class, 'checkIfHasStatus'])->middleware(['auth']);
        Route::post('/exportTemplate', [OrderController::class, 'exportTemplate'])->middleware(['auth']);
        Route::post('/createNewMessage', [OrderController::class, 'createNewMessage'])->middleware(['auth']);
        Route::post('/twilio/send-sms', [TwilioController::class, 'sendSms'])->middleware(['auth']);
        Route::post('/{id}', [OrderController::class, 'update'])->middleware(['auth']);
        Route::get('/{id}', [OrderController::class, 'show']);
        Route::post('/{id}/upload-document', [OrderController::class, 'uploadDocument']);
        Route::put('/changeStatus', [OrderController::class, 'changeStatus']);
    });

    Route::prefix('accounts')->group(function () {
        Route::post('/', [AccountController::class, 'store'])->middleware(['auth']);
        Route::post('/index/{id}', [AccountController::class, 'index'])->middleware(['auth']);
        Route::post('/indexWithoutPagination/{id}', [AccountController::class, 'indexWithoutPagination'])->middleware(['auth']);
        Route::get('/checkCIF', [AccountController::class, 'checkCIF'])->middleware(['auth']);
        Route::get('/getCIFByCUPS', [AccountController::class, 'getCIFByCUPS'])->middleware(['auth']);
        Route::post('/indexFilters/{id}', [AccountController::class, 'indexFilters']);
        Route::get('/{id}', [AccountController::class, 'show'])->middleware(['auth']);
        Route::post('/update', [AccountController::class, 'update'])->middleware(['auth']);
        Route::post('/getDatadisAccounts/{id}', [AccountController::class, 'getDatadisAccounts'])->middleware(['auth']);
        Route::delete('/{id}', [AccountController::class, 'deleteAccount'])->middleware(['auth']);
        Route::post('/deleteAllSelected', [AccountController::class, 'deleteAllSelectedAccounts'])->middleware(['auth']);
        Route::post('/toggleArchiveAccount/{id}', [AccountController::class, 'toggleArchiveAccount'])->middleware(['auth']);
        Route::post('/toggleArchiveSelectedAccounts', [AccountController::class, 'toggleArchiveSelectedAccounts'])->middleware(['auth']);
        Route::post('/getRelatedAccounts/{id}', [AccountController::class, 'getRelatedAccounts'])->middleware(['auth']);
        Route::post('/dumpAccounts', [AccountController::class, 'dumpAccounts'])->middleware(['auth']);
        Route::get('/checkByUserAndIdentifier/{id}', [AccountController::class, 'checkByUserAndIdentifier'])->middleware(['auth']);
        Route::post('/export', [AccountController::class, 'exportAccountsTemplate'])->middleware(['auth']);
    });

    Route::prefix('/marketers')->group(function () {
        Route::post('/', [MarketerController::class, 'store'])->middleware(['auth']);
        Route::get('/', [MarketerController::class, 'index']);
        Route::get('/all', [MarketerController::class, 'indexAll'])->middleware(['auth']);
        Route::put('/deleteProductFee', [MarketerController::class, 'deleteProductFee'])->middleware(['auth']);
        Route::put('/addProductFee', [MarketerController::class, 'addProductFee'])->middleware(['auth']);
        Route::get('/checkIfDualProductExists', [MarketerController::class, 'checkIfDualProductExists'])->middleware(['auth']);
        Route::get('/checkIfProductExists', [MarketerController::class, 'checkIfProductExists'])->middleware(['auth']);
        Route::get('/getEnterpiseRanges', [MarketerController::class, 'getEnterpiseRanges'])->middleware(['auth']);
        Route::get('/{id}', [MarketerController::class, 'show'])->middleware(['auth']);
        Route::post('/generalUpdateMarketers', [MarketerController::class, 'generalUpdateMarketers'])->middleware(['auth']);
        Route::post('/dumpMarketers', [MarketerController::class, 'dumpMarketers'])->middleware(['auth']);
        Route::post('/dumpMarketerCommissions', [MarketerController::class, 'dumpMarketerCommissions'])->middleware(['auth']);
        Route::post('/haveMarketerOrders', [MarketerController::class, 'haveMarketerOrders'])->middleware(['auth']);
        Route::post('/{id}', [MarketerController::class, 'update'])->middleware(['auth']);
        Route::delete('/{id}', [MarketerController::class, 'delete'])->middleware(['auth']);
    });

    Route::prefix('contacts')->group(function () {
        Route::post('/', [ContactController::class, 'store'])->middleware(['auth']);
        Route::post('/index/{id}', [ContactController::class, 'index'])->middleware(['auth']);
        Route::post('/indexWithoutPagination/{id}', [ContactController::class, 'indexWithoutPagination'])->middleware(['auth']);
        Route::get('/{id}', [ContactController::class, 'show'])->middleware(['auth']);
        Route::post('/update', [ContactController::class, 'update'])->middleware(['auth']);
        Route::delete('/{id}', [ContactController::class, 'deleteContact'])->middleware(['auth']);
        Route::post('/deleteAllSelected', [ContactController::class, 'deleteAllSelectedContact'])->middleware(['auth']);
        Route::post('/toggleArchiveContact/{id}', [ContactController::class, 'toggleArchiveContact'])->middleware(['auth']);
        Route::post('/toggleArchiveSelectedContacts', [ContactController::class, 'toggleArchiveSelectedContacts'])->middleware(['auth']);
        Route::post('/getAccountsRelated/{id}', [ContactController::class, 'getAccountsRelated'])->middleware(['auth']);
        Route::get('/getContactOpportunities/{id}', [ContactController::class, 'getContactOpportunities'])->middleware(['auth']);
    });

    Route::prefix('objectives')->group(function (){
        Route::post('/index', [ObjectivesController::class, 'index']);
        Route::post('/', [ObjectivesController::class, 'store']);
        Route::post('/{id}', [ObjectivesController::class, 'update']);
        Route::delete('/{id}', [ObjectivesController::class, 'delete']);

    });

    Route::prefix('opportunities')->group(function () {
        Route::post('/', [OportunityController::class, 'store'])->middleware(['auth']);
        Route::post('/importCargacarOpp', [OportunityController::class, 'importCargacarHtmlExcel']);
        Route::post('/createNewMessage', [OportunityController::class, 'createNewMessage']);
        Route::post('/meta/leads', [OportunityController::class, 'importFacebookLeads']);
        Route::post('/sendEvChargerPDF', [OportunityController::class, 'sendEvChargerPDF']);
        Route::post('/generateEvChargerPDF', [OportunityController::class, 'generateEvChargerPDF']);
        Route::post('/index/{id}', [OportunityController::class, 'index'])->middleware(['auth']);
        Route::post('/indexFilters/{id}', [OportunityController::class, 'indexFilters'])->middleware(['auth']);
        Route::post('/indexWithoutPagination/{id}', [OportunityController::class, 'indexWithoutPagination'])->middleware(['auth']);
        Route::get('/{id}', [OportunityController::class, 'show'])->middleware(['auth']);
        Route::post('/update', [OportunityController::class, 'update'])->middleware(['auth']);
        Route::delete('/{id}', [OportunityController::class, 'deleteOpportunity'])->middleware(['auth']);
        Route::post('/deleteAllSelected', [OportunityController::class, 'deleteAllSelectedOpportunities'])->middleware(['auth']);
        Route::post('/toggleArchiveOpportunity/{id}', [OportunityController::class, 'toggleArchiveOpportunity'])->middleware(['auth']);
        Route::post('/toggleArchiveSelectedOpportunities', [OportunityController::class, 'toggleArchiveSelectedOpportunities'])->middleware(['auth']);
        Route::post('/createAccFromOpp', [OportunityController::class, 'createAccFromOpp'])->middleware(['auth']);
        Route::post('/getRelatedContacts/{id}', [OportunityController::class, 'getRelatedContacts'])->middleware(['auth']);
        Route::get('/getRelatedAccounts/{id}', [OportunityController::class, 'getRelatedAccounts'])->middleware(['auth']);
        Route::get('/getRelatedAccountsByCIF', [OportunityController::class, 'getRelatedAccountsByCIF'])->middleware(['auth']);
    });

    Route::prefix('tasks')->group(function () {
        Route::post('/', [TaskController::class, 'store'])->middleware(['auth']);
        Route::put('/update/{id}', [TaskController::class, 'update'])->middleware(['auth']);
        Route::get('/index/{id}', [TaskController::class, 'index'])->middleware(['auth']);
        Route::get('/show/{id}', [TaskController::class, 'show'])->middleware(['auth']);
        Route::put('/toggleSubTask', [TaskController::class, 'toggleSubTask'])->middleware(['auth']);
        Route::put('/toggleTask', [TaskController::class, 'toggleTask'])->middleware(['auth']);
        Route::delete('/{id}', [TaskController::class, 'deleteTask'])->middleware(['auth']);
    });

    Route::prefix('calendar')->group(function () {
        Route::post('/', [EventController::class, 'store'])->middleware(['auth']);
        Route::put('/', [EventController::class, 'update'])->middleware(['auth']);
        Route::get('/', [EventController::class, 'index'])->middleware(['auth']);
        Route::get('/{id}', [EventController::class, 'show'])->middleware(['auth']);
        Route::delete('/{id}', [EventController::class, 'deleteEvent'])->middleware(['auth']);
    });

    Route::prefix('documents')->group(function () {
        Route::post('/', [DocumentController::class, 'listFolder'])->middleware(['auth']);
        Route::post('/deleteFileOrFolder', [DocumentController::class, 'deleteFileOrFolder'])->middleware(['auth']);
        Route::post('/createFolder', [DocumentController::class, 'createFolder'])->middleware(['auth']);
        Route::post('/createFile', [DocumentController::class, 'createFile'])->middleware(['auth']);
        Route::put('/updateFileOrFolder', [DocumentController::class, 'updateFileOrFolder'])->middleware(['auth']);
        Route::post('/downloadFile', [DocumentController::class, 'downloadFile'])->middleware(['auth']);
    });

    Route::prefix('select')->group(function () {
        Route::get('/', [SelectController::class, 'index'])->middleware(['auth']);
        Route::post('/', [SelectController::class, 'addSelectType'])->middleware(['auth']);
        Route::delete('/', [SelectController::class, 'delSelectType'])->middleware(['auth']);
    });

    Route::prefix('GEO')->group(function () {
        Route::get('/getCommunities', [GEOController::class, 'getCommunities'])->middleware(['auth']);
        Route::get('/getProvinces/{community}', [GEOController::class, 'getProvinces'])->middleware(['auth']);
        Route::get('/getLocalities/{province}', [GEOController::class, 'getLocalities'])->middleware(['auth']);
    });

    Route::prefix('liquidations')->group(function () {
        Route::get('/', [LiquidationController::class, 'index'])->middleware(['auth']);
        Route::get('/liquidate', [LiquidationController::class, 'liquidate'])->middleware(['auth']);
        Route::post('/liquidateUser', [LiquidationController::class, 'liquidateUser'])->middleware(['auth']);
        Route::get('/users', [LiquidationController::class, 'fetchUsersToLiquidate'])->middleware(['auth']);
        Route::post('/liquidateLiquidation', [LiquidationController::class, 'liquidateLiquidation'])->middleware(['auth']);
        Route::delete('/{id}', [LiquidationController::class, 'deleteLiquidation'])->middleware(['auth']);
    });

    Route::prefix('tools')->group(function () {
        Route::post('/liquidateCUPS', [ToolsController::class, 'liquidateCUPS'])->middleware(['auth']);
        Route::post('/getAPIDataForInvoice', [ToolsController::class, 'getAPIDataForInvoice'])->middleware(['auth']);
        Route::post('/generateDatadisReport', [ToolsController::class, 'generateDatadisReport'])->middleware(['auth']);
        Route::post('/generatePowerOptimizerReport', [ToolsController::class, 'generatePowerOptimizerReport'])->middleware(['auth']);
        Route::post('/getAPIDataForInvoices', [ToolsController::class, 'getAPIDataForInvoices'])->middleware(['auth']);
        Route::get('datadis/obtainDatadisTokenInvoice', [ToolsController::class, 'obtainDatadisTokenInvoice']);
        Route::post('/generateElectricityPDF', [ToolsController::class, 'previewCustomComparativePdf'])->middleware(['auth']);
        Route::get('/datadisContracts', [ToolsController::class, 'getDatadisContractByCups']);
        Route::post('massiveEmail', [ToolsController::class, 'massiveEmail']);
        Route::post('massiveEmail/prepare-doc', [ToolsController::class, 'prepareMassiveEmailDoc']);
        Route::post('/prepareMassiveEmailDoc', [ToolsController::class, 'prepareMassiveEmailDoc']);
        Route::post('/generateGasPDF', [ToolsController::class, 'generateGasPDF'])->middleware(['auth']);
        Route::get("/getAPIConsumption", [ToolsController::class, 'getAPIConsumption'])->middleware(['auth']);
        Route::post("/getOCRData", [ToolsController::class, 'getOCRData']);
        Route::post("/getGasOCRData", [ToolsController::class, 'getGasOCRData'])->middleware(['auth']);
        Route::get('/obtainDatadisToken',[ToolsController::class, 'obtainDatadisToken'])->middleware(['auth']);
        Route::post('/getDatadisConsumptionData',[ToolsController::class, 'getDatadisConsumptionData'])->middleware(['auth']);
        Route::put('/statuses',[ToolsController::class, 'updateStatuses'])->middleware(['auth']);
        Route::post('/massiveEmail',[ToolsController::class, 'sendMassiveEmail'])->middleware(['auth']);
        Route::get('/getEmails/{id}',[ToolsController::class, 'getEmails'])->middleware(['auth']);
        Route::get('/getAllEmails', [ToolsController::class, 'getAllEmails'])->middleware(['auth']);
        Route::post('/statesMassive',[ToolsController::class, 'statesMassive'])->middleware(['auth']);
        Route::post('/uploadQuillImage',[ToolsController::class, 'uploadQuillImage'])->middleware(['auth']);
        Route::post('log/invoice', [ToolsController::class, 'invoiceChecker'])->middleware(['auth']);
        Route::prefix('segenet')->group(function () {
            Route::get("/getProbesAvailable", [ToolsController::class, 'getProbesAvailable'])->middleware(['auth']);
            Route::get("/getProbeInfo", [ToolsController::class, 'getProbeInfo'])->middleware(['auth']);
            Route::get("/getProbeData", [ToolsController::class, 'getProbeData'])->middleware(['auth']);
            Route::get("/excelQuarters", [ToolsController::class, 'excelQuarters'])->middleware(['auth']);
            Route::get("/excelCloses", [ToolsController::class, 'excelCloses'])->middleware(['auth']);
            Route::get("/excelMockInvoice", [ToolsController::class, 'excelMockInvoice'])->middleware(['auth']);
            Route::post('storedInvoices', [ToolsController::class, 'storedInvoices'])->middleware('auth');
            Route::get('storedInvoices/download/{enterpriseId}/{file}', [ToolsController::class, 'storedInvoiceDownload']);
        });
    });


    //Google API
    Route::prefix('google')->group(function () {
        Route::get('/getTokens', [GoogleController::class, 'getTokens'])->middleware(['auth']);
        Route::get('/getNewToken', [GoogleController::class, 'getNewToken'])->middleware(['auth']);
        Route::post('/checkSignedIn', [GoogleController::class, 'checkSignedIn'])->middleware(['auth']);
        Route::get('/listEvents', [GoogleController::class, 'listEvents'])->middleware(['auth']);
        Route::get('/getEvent/{id}', [GoogleController::class, 'getEvent'])->middleware(['auth']);
        Route::post('/createEvent', [GoogleController::class, 'createEvent'])->middleware(['auth']);
        Route::put('/updateEvent/{id}', [GoogleController::class, 'updateEvent'])->middleware(['auth']);
        Route::delete('/deleteEvent/{id}', [GoogleController::class, 'deleteEvent'])->middleware(['auth']);
    });

    Route::prefix('twilio')->group(function () {
        Route::post('/startCall', [TwilioController::class, 'startCall'])->middleware(['auth']);
        Route::post('/getCallStatus', [TwilioController::class, 'getCallStatus'])->middleware(['auth']);
        Route::post('/downloadNaturgyCall', [TwilioController::class, 'downloadNaturgyCall'])->middleware(['auth']);
        Route::get('/voice-token', [TwilioController::class, 'getVoiceToken'])->middleware(['auth']);

        // Comprobar saldo antes de llamar
        Route::get('/availableCallMinutes', [TwilioController::class, 'availableCallMinutes'])->middleware(['auth']);
    });

    Route::prefix('stripe')->group(function () {
        //Suscripción
        Route::post('/checkout', [StripeController::class, 'createSession']);
        Route::post('/success', [StripeController::class, 'successSession']);
        Route::get('/subscription', [StripeController::class, 'getSubscription'])->middleware(['auth']);
        Route::get('/subscriptionInvoice', [StripeController::class, 'getSubscriptionInvoice'])->middleware(['auth']);
        Route::post('/updateSubscription', [StripeController::class, 'updateSubscription'])->middleware(['auth']);
        Route::post('/changeSubscriptionInterval', [StripeController::class, 'changeSubscriptionInterval'])->middleware(['auth']);

        // Acciones suscripción
        Route::post('/portalSession', [StripeController::class, 'createPortalSession'])->middleware(['auth']);

        //Extras
        Route::get('/extras', [StripeController::class, 'getExtras'])->middleware(['auth']);
        Route::post('/oneTimeExtraCheckout', [StripeController::class, 'createOneTimeExtraCheckout'])->middleware(['auth']);
        Route::post('/addRecurringExtra', [StripeController::class, 'addRecurringExtra'])->middleware(['auth']);
        Route::post('/cancelRecurringExtra', [StripeController::class, 'cancelRecurringExtra'])->middleware(['auth']);

        //Cargador eléctrico
        Route::post('/createElectricChargerBudgetCheckout', [StripeController::class, 'createElectricChargerBudgetCheckout']);
        Route::post('/getElectricChargerBudgetCheckoutSession', [StripeController::class, 'getElectricChargerBudgetCheckoutSession']);
    });

    Route::prefix('comparatives')->group(function () {
       Route::get('/', [ComparativeController::class, 'index'])->middleware(['auth']);
       Route::post('/store', [ComparativeController::class, 'store'])->middleware(['auth']);
       Route::post('/update', [ComparativeController::class, 'update'])->middleware(['auth']);
       Route::post('/countValidBillComparative', [ComparativeController::class, 'countValidBillComparative'])->middleware(['auth']);
       Route::delete('/{id}', [ComparativeController::class, 'destroy'])->middleware(['auth']);
    });

    Route::prefix('sips')->group(function () {
        Route::get('/getGasConsumption', [SipsController::class, 'getGasConsumption'])->middleware('auth');
        Route::get('/getGasCupsByAddress', [SipsController::class, 'getGasCupsByAddress'])->middleware('auth');
    });

    Route::prefix('signin')->group(function () {
        Route::get('/index', [SigningController::class, 'index'])->middleware(['auth']);
        Route::get('/monthly-summary', [SigningController::class, 'getMonthlySummary'])->middleware(['auth']);
        Route::get('/{id}/audit-logs', [SigningController::class, 'getSigninAuditLogs'])->middleware(['auth']);
        Route::post('/forgotten-request', [SigningController::class, 'createForgottenSigningRequest'])->middleware(['auth']);
        Route::get('/forgotten-requests', [SigningController::class, 'getForgottenSigningRequests'])->middleware(['auth']);
        Route::post('/forgotten-requests/{id}/approve', [SigningController::class, 'approveForgottenSigningRequest'])->middleware(['auth']);
        Route::post('/forgotten-requests/{id}/reject', [SigningController::class, 'rejectForgottenSigningRequest'])->middleware(['auth']);
        Route::get('/{id}', [SigningController::class, 'show'])->middleware(['auth']);
        Route::get('/user/{userId}', [SigningController::class, 'showAllByUser'])->middleware(['auth']);
        Route::post('/saveSignings', [SigningController::class, 'saveSignings'])->middleware(['auth']);
        Route::get('/user/{userId}/date/{date1}/{date2}', [SigningController::class, 'showByUserAndDate'])->middleware(['auth']);
        Route::get('/user/{userId}/last', [SigningController::class, 'getLastStatus']);
        Route::post('/vacation-request', [SigningController::class, 'createVacationRequest'])->middleware(['auth']);
        Route::put('/{id}', [SigningController::class, 'editSigning'])->middleware(['auth']);
        Route::delete('/{id}', [SigningController::class, 'deleteSigning'])->middleware(['auth']);
        Route::get('/user/paginated', [SigningController::class, 'getPaginatedSignins'])->middleware(['auth']);
        Route::post('/{signinId}/uploadWorkOrder', [SigningController::class, 'attachWorkOrder'])->middleware(['auth']);
        Route::get('/report/pdf', [SigningController::class, 'generateReportPdf'])->middleware(['auth']);
        Route::get('/report/excel', [SigningController::class, 'generateReportExcel'])->middleware(['auth']);

         // Calendario laboral
        Route::get('/work-calendar/events', [SigningController::class, 'getWorkCalendarEvents'])->middleware(['auth']);
        Route::post('/work-calendar/events', [SigningController::class, 'createWorkCalendarEvent'])->middleware(['auth']);
        Route::put('/work-calendar/events/{id}', [SigningController::class, 'updateWorkCalendarEvent'])->middleware(['auth']);
        Route::delete('/work-calendar/events/{id}', [SigningController::class, 'deleteWorkCalendarEvent'])->middleware(['auth']);
        Route::get('/work-calendar/summary', [SigningController::class, 'getWorkCalendarSummary'])->middleware(['auth']);
    });

    //Comparativas
    Route::post('/generatePDF', [ToolsController::class, 'generateElectricityPDF'])->middleware(['auth']);

    //Webhooks
    Route::prefix('webhooks')->group(function () {
        Route::post('/mailgun', [WebhookController::class, 'mailgun']);
    });

    //Logs
    Route::prefix('logs')->group(function () {
        Route::get('/', [LogController::class, 'getLogs']);
        Route::post('/generateComparative', [ToolsController::class, 'generateComparativeLog'])->middleware(['auth']);
    });

    //Comparador abierto
    Route::prefix('openComparator')->group(function () {
        Route::post('/registerOrder', [OpenComparatorController::class, 'registerOrder']);
        Route::get('/floatingcontact', [OpenComparatorController::class, 'getFloatingContact']);
        Route::post('/registerAlarmOpportunity', [OpenComparatorController::class, 'registerAlarmOpportunity']);
        Route::post('/registerAutoconsumoOpportunity', [OpenComparatorController::class, 'registerAutoconsumoOpportunity']);
        Route::post('/registerCarChargerOpportunity', [OpenComparatorController::class, 'registerCarChargerOpportunity']);
        Route::post('/registerClaimOpportunity', [OpenComparatorController::class, 'registerClaimOpportunity']);
    });

    //Enterprise
    Route::prefix('enterprises')->group(function () {
        Route::get('/{id}',[EnterpriseController::class, 'show']);
    });

    //Comprobar versión del build de vite
    Route::get('/version-check', function () {
        return response()
            ->json(['version' => ManifestVersion::hash()])
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    });
});

Route::get('/politica-privacidad', [PrivacyPolicyController::class, 'index'])->middleware(['guest']);

Route::get('/dry-run-email-orders', [CronJobController::class, 'dryRunEmailOrders']);



Route::post(
    '/sendEmailOrders',
    [CronJobController::class, 'sendEmailOrders']
)->middleware(['auth', 'onlyUser:soporte@zocoenergia.com']);

Route::get(
    '/collection',
    [CronJobController::class, 'collection']
)->middleware(['auth', 'onlyUser:soporte@zocoenergia.com']);

Route::get(
    '/changeStatusKuvi',
    [CronJobController::class, 'changeStatusKuvi']
)->middleware(['auth', 'onlyUser:soporte@zocoenergia.com']);

// - portal
Route::get('/zoco-one', function () {
    return view('app');
})->middleware(['auth']);


Route::get('/{page}', function () {
    return view('app');
})->whereIn('page', [
    'comparator',
    'comparatortelefonia',
    'comparatoralarma',
    'autoconsumo',
    'reclamaciones',
    'cargadordecoche',
]);

/*
Route::prefix('comparadorluzygas')->group(function () {
    Route::get('/', function () {return view('app');});
    Route::get('/comparadordeluzygas', function () {return view('app');});
    Route::get('/comparadortelefonia', function () {return view('app');});
    Route::get('/comparadoralarma', function () {return view('app');});

});
*/

Route::get('/orders/documents/{userId}', function () {
    return view('app');
});

Route::get('/portal/{any?}', function () {
    return view('app');
})->where('any', '.*')->name('portal')->middleware(['guest']);

// - general
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '.*')->name('app')->middleware(['auth']);




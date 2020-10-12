<?php

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

Route::get('/', function () {
    return response()->json([
        'success' => true,
        'message' => 'Welcome to API Diamond Trading'
    ]);
});

# Images
Route::get('profiles/{image}', 'ImageController@profiles');
Route::get('receipts/{image}', 'ImageController@receipts');

# Notifications
Route::get('notification/bitcoinPayment', 'NotificationController@bitcoinPayment')->name('notificationBitcoin');

# Payments
Route::get('payment/income', 'PaymentController@income');
Route::get('payment/bonus', 'PaymentController@bonus');
Route::get('teste', 'PaymentController@teste');

# Auth
Route::get('allPlans', 'Client\PlanController@allPlans');
Route::get('validateManager/{manager}', 'Client\AuthController@validateManager');
Route::get('validateUsername/{username}', 'Client\AuthController@validateUsername');
Route::post('register', 'Client\AuthController@register');
Route::post('login', 'Client\AuthController@login')->middleware('assign.guard:users');
Route::post('forgotPassword', 'Client\AuthController@forgotPassword');
Route::post('redefinePassword', 'Client\AuthController@redefinePassword');
Route::get('sendConfirmEmail', 'Client\AuthController@sendConfirmEmail');
Route::post('confirmEmail', 'Client\AuthController@confirmEmail');

Route::group(['middleware' => ['auth.jwt', 'assign.guard:users']], function () {

    # Auth
    Route::get('logout', 'Client\AuthController@logout');
    Route::get('auth', 'Client\AuthController@auth');

    # Plans
    Route::get('availablePlans', 'Client\PlanController@availablePlans');
    Route::get('currentPlan', 'Client\PlanController@currentPlan');
    Route::get('careerResume', 'Client\PlanController@careerResume');
    Route::get('currentCareer', 'Client\PlanController@currentCareer');
    Route::post('hirePlan', 'Client\PlanController@hirePlan');

    # Extracts
    Route::get('extracts', 'Client\ExtractController@extracts');
    Route::get('extract/{extract}', 'Client\ExtractController@extract');

    # Transactions
    Route::get('transactions', 'Client\TransactionController@transactions');
    Route::get('transaction/{transaction}', 'Client\TransactionController@transaction');
    Route::get('openPayment', 'Client\TransactionController@openPayment');
    Route::post('payWithBitcoin', 'Client\TransactionController@payWithBitcoin');
    Route::post('sendReceipt', 'Client\TransactionController@sendReceipt');
    Route::get('withdrawDay', 'Client\TransactionController@withdrawDay');
    Route::post('withdrawIncome', 'Client\TransactionController@withdrawIncome');
    Route::post('withdrawBonus', 'Client\TransactionController@withdrawBonus');
    Route::get('chargebackStatus', 'Client\TransactionController@chargebackStatus');
    Route::get('calculateChargeback', 'Client\TransactionController@calculateChargeback');
    Route::post('chargeback', 'Client\TransactionController@chargeback');
    Route::post('lastDraw', 'Client\TransactionController@lastDraw');
    Route::get('listLastDraw', 'Client\TransactionController@listLastDraw');

    # Network
    Route::get('binary', 'Client\NetworkController@binary');
    Route::get('binary/{username}', 'Client\NetworkController@find');
    Route::get('directs', 'Client\NetworkController@directs');
    Route::get('gainResume', 'Client\NetworkController@gainResume');
    Route::get('scoreResume', 'Client\NetworkController@scoreResume');
    Route::post('changeBinaryKey', 'Client\NetworkController@changeBinaryKey');
    Route::get('report', 'Client\NetworkController@report');
    Route::get('report/{plan}', 'Client\NetworkController@reportByPlan');

    # Setting
    Route::post('changePhoto', 'Client\SettingController@changePhoto');
    Route::get('validateUsernameProfile/{username}', 'Client\SettingController@validateUsername');
    Route::post('changeProfile', 'Client\SettingController@changeProfile');
    Route::post('changeAddress', 'Client\SettingController@changeAddress');
    Route::post('uploadDocument', 'Client\SettingController@uploadDocument');
    Route::get('documents', 'Client\SettingController@documents');
    Route::get('bankings', 'Client\SettingController@bankings');
    Route::post('changeBanking', 'Client\SettingController@changeBanking');
    Route::post('changeWallet', 'Client\SettingController@changeWallet');
    Route::post('changePassword', 'Client\SettingController@changePassword');

    # Google2FA
    Route::get('googleAuth', 'Client\Google2FAController@googleAuth');
    Route::post('verifyGoogleAuth', 'Client\Google2FAController@verifyGoogleAuth');
    Route::post('disableGoogleAuth', 'Client\Google2FAController@disableGoogleAuth');

    # Ticket
    Route::get('tickets', 'Client\TicketController@tickets');
    Route::post('createTicket', 'Client\TicketController@createTicket');

});


# Admin
Route::prefix('admin')->group(function () {

    # Auth
    Route::post('login', 'Adm\AuthController@login')->middleware('assign.guard:admins');

    Route::group(['middleware' => ['auth.jwt', 'assign.guard:admins']], function () {

        Route::get('auth', 'Adm\AuthController@auth');
        Route::get('logout', 'Adm\AuthController@logout');

        # Client
        Route::get('clients', 'Adm\ClientController@clients');
        Route::get('client/{token}', 'Adm\ClientController@client');
        Route::post('client/authenticate', 'Adm\ClientController@authenticate');
        Route::put('client/block', 'Adm\ClientController@block');
        Route::put('client/unlock', 'Adm\ClientController@unlock');
        Route::post('client/verifyUsername', 'Adm\ClientController@verifyUsername');
        Route::put('client/updateProfile', 'Adm\ClientController@updateProfile');
        Route::get('additionalScore', 'Adm\ClientController@additionalScore');
        Route::get('gainLimits', 'Adm\ClientController@gainLimits');
        Route::get('documents', 'Adm\ClientController@documents');
        Route::get('balanceInfo', 'Adm\ClientController@balanceInfo');

        # Extracts
        Route::get('lastBonusPayment', 'Adm\ExtractController@lastBonusPayment');
        Route::get('lastIncomePayment', 'Adm\ExtractController@lastIncomePayment');

        # Transactions
        Route::get('activations', 'Adm\TransactionController@activations');
        Route::get('activations/{limit}', 'Adm\TransactionController@activations');
        Route::get('invoicesResume', 'Adm\TransactionController@invoicesResume');
        Route::get('withdrawsResume', 'Adm\TransactionController@withdrawsResume');
        Route::get('invoices', 'Adm\TransactionController@invoices');
        Route::get('invoice/{id}', 'Adm\TransactionController@invoice');
        Route::post('payInvoice', 'Adm\TransactionController@payInvoice');
        Route::post('applyVoucher', 'Adm\TransactionController@applyVoucher');
        Route::get('withdrawals', 'Adm\TransactionController@withdrawals');
        Route::get('withdrawal/{id}', 'Adm\TransactionController@withdrawal');
        Route::post('confirmWithdrawal', 'Adm\TransactionController@confirmWithdrawal');
        Route::post('refuseWithdrawal', 'Adm\TransactionController@refuseWithdrawal');

        # Plans
        Route::get('plans', 'Adm\PlanController@plans');
        Route::get('ratioActivePlans', 'Adm\PlanController@ratioActivePlans');

        # Network
        Route::get('network', 'Adm\NetworkController@network');
        Route::get('network/{username}', 'Adm\NetworkController@find');
        Route::get('network/rootInfo/{username}', 'Adm\NetworkController@rootInfo');

        # Ticket
        Route::get('tickets', 'Adm\TicketController@tickets');

        # System
        Route::get('administrators', 'Adm\SystemController@administrators');
        Route::get('logs', 'Adm\SystemController@logs');
        Route::get('incomeToday', 'Adm\SystemController@incomeToday');
        Route::get('withdrawInfo', 'Adm\SystemController@withdrawInfo');

    });
});

<?php

use App\Http\Controllers\AccountingController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BudgetLineController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\ContractItemController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentStageController;
use App\Http\Controllers\PenaltyDecisionController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::redirect('/', '/contracts');

    Route::resource('contracts', ContractController::class)->except(['destroy']);

    Route::prefix('contracts/{contract}')->name('contracts.')->scopeBindings()->group(function () {
        Route::post('activate', [ContractController::class, 'activate'])->name('activate');

        Route::post('items', [ContractItemController::class, 'store'])->name('items.store');
        Route::delete('items/{item}', [ContractItemController::class, 'destroy'])->name('items.destroy');

        Route::patch('stages/{stage}', [PaymentStageController::class, 'update'])->name('stages.update');

        Route::get('deliveries/create', [DeliveryController::class, 'create'])->name('deliveries.create');
        Route::post('deliveries', [DeliveryController::class, 'store'])->name('deliveries.store');

        Route::post('penalty/approve', [PenaltyDecisionController::class, 'approve'])->name('penalty.approve');
        Route::post('penalty/reject', [PenaltyDecisionController::class, 'reject'])->name('penalty.reject');

        Route::get('accounting', [AccountingController::class, 'show'])->name('accounting');
        Route::post('payments', [PaymentController::class, 'store'])->name('payments.store');
        Route::post('payments/{payment}/void', [PaymentController::class, 'void'])->name('payments.void');
    });

    Route::resource('budget-lines', BudgetLineController::class)->except(['create', 'show']);
});

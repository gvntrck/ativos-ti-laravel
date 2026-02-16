<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ComputerController;
use App\Http\Controllers\CellphoneController;
use App\Http\Controllers\HistoryController;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('computers', ComputerController::class);
    Route::post('computers/{computer}/history', [HistoryController::class, 'storeComputerHistory'])->name('computers.history.store');

    Route::resource('cellphones', CellphoneController::class);
    Route::post('cellphones/{cellphone}/history', [HistoryController::class, 'storeCellphoneHistory'])->name('cellphones.history.store');

    Route::get('/reports', [\App\Http\Controllers\ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/computers', [\App\Http\Controllers\ReportController::class, 'exportComputers'])->name('reports.computers');
    Route::get('/reports/cellphones', [\App\Http\Controllers\ReportController::class, 'exportCellphones'])->name('reports.cellphones');
});

require __DIR__ . '/auth.php'; // Will need to define auth routes later or assume Breeze

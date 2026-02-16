<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ComputerController;
use App\Http\Controllers\CellphoneController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\ReportController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('computers', ComputerController::class);
    Route::resource('cellphones', CellphoneController::class);

    // History routes
    Route::post('/computers/{computer}/history', [HistoryController::class, 'storeComputerHistory'])->name('computers.history.store');
    Route::post('/cellphones/{cellphone}/history', [HistoryController::class, 'storeCellphoneHistory'])->name('cellphones.history.store');

    // Reports routes
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/computers', [ReportController::class, 'exportComputers'])->name('reports.computers');
    Route::get('/reports/cellphones', [ReportController::class, 'exportCellphones'])->name('reports.cellphones');
});

require __DIR__ . '/auth.php';

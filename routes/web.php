<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\TaomController;

Route::get('/', [SiteController::class, 'showSite'])->name('site');
Route::get('/test', function () { return view('test'); });

Auth::routes();
Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [SiteController::class, 'showAdmin'])->name('dashboard');
    Route::post('/taomlar', [TaomController::class, 'store'])->name('taomlar.store');
    Route::put('/taomlar/{taom}', [TaomController::class, 'update'])->name('taomlar.update');
    Route::delete('/taomlar/{taom}', [TaomController::class, 'destroy'])->name('taomlar.destroy');
});

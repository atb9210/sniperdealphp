<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\KeywordFormController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Keyword Form Routes
    Route::get('/keyword', [KeywordFormController::class, 'index'])->name('keyword.index');
    Route::post('/keyword', [KeywordFormController::class, 'store'])->name('keyword.store');
});

require __DIR__.'/auth.php';

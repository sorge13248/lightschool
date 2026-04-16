<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\DiaryController;
use App\Http\Controllers\FileManagerController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShareController;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\TrashController;
use App\Http\Controllers\WriterController;
use Illuminate\Support\Facades\Route;

// File serve — no session required; serveFile enforces ownership / sharing /
// bypass-token logic internally.
Route::get('/file/{id}', [FileManagerController::class, 'serveFile'])
    ->where('id', '[0-9]+')
    ->name('api.file');

// File Manager — uses its own mixed-auth middleware so that `provide-file`
// is accessible without a session (bypass tokens, profile pictures).
Route::match(['get', 'post'], '/file-manager', [FileManagerController::class, 'handle'])
    ->middleware(['file-manager-auth', 'throttle:api'])
    ->name('api.file-manager');

Route::middleware(['auth', 'throttle:api'])->group(function () {
    Route::match(['get', 'post'], '/trash',     [TrashController::class,     'handle'])->name('api.trash');
    Route::match(['get', 'post'], '/share',     [ShareController::class,     'handle'])->name('api.share');
    Route::match(['get', 'post'], '/contact',   [ContactController::class,   'handle'])->name('api.contact');
    Route::match(['get', 'post'], '/diary',     [DiaryController::class,     'handle'])->name('api.diary');
    Route::match(['get', 'post'], '/message',   [MessageController::class,   'handle'])->name('api.message');
    Route::match(['get', 'post'], '/settings',  [SettingsController::class,  'handle'])->name('api.settings');
    Route::match(['get', 'post'], '/timetable', [TimetableController::class, 'handle'])->name('api.timetable');
    Route::match(['get', 'post'], '/writer',    [WriterController::class,    'handle'])->name('api.writer');
    Route::match(['get', 'post'], '/project',   [ProjectController::class,   'handle'])->name('api.project');
});

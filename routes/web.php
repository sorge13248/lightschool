<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasskeyLoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\VerifyController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\DataExportController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PasskeyController;
use Illuminate\Support\Facades\Route;
use Spatie\LaravelPasskeys\Http\Controllers\GeneratePasskeyAuthenticationOptionsController;

// ─── Public pages ────────────────────────────────────────────────────────────

Route::get('/',         [PageController::class, 'home'])->name('home');
Route::get('/overview', [PageController::class, 'overview'])->name('overview');
Route::get('/features', [PageController::class, 'features'])->name('features');
Route::get('/privacy',  [PageController::class, 'privacy'])->name('privacy');
Route::get('/tos',      [PageController::class, 'tos'])->name('tos');
Route::get('/cookie',   [PageController::class, 'cookie'])->name('cookie');

// Language
Route::get('/language',     [PageController::class, 'languagePage'])->name('language');
Route::get('/language/set', [PageController::class, 'setLanguage'])->name('language.set');

// Translations as a cacheable JS file — used by layouts instead of inline @json
Route::get('/lang/{locale}.js', function (string $locale) {
    $available = array_map(fn($f) => pathinfo($f, PATHINFO_FILENAME), glob(lang_path('*.json')));
    if (!in_array($locale, $available, true)) {
        $locale = config('app.locale', 'en');
    }
    $translations = app('translator')->getLoader()->load($locale, '*', '*') ?: [];
    return response('var LANGUAGE = ' . json_encode($translations) . ';', 200, [
        'Content-Type'  => 'application/javascript; charset=utf-8',
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->where('locale', '[a-z]{2,5}')->name('lang.js');

// ─── Theme-aware icon serving ─────────────────────────────────────────────────

Route::get('/img/icon/{type}/{name}', [PageController::class, 'icon'])
    ->where('type', 'app|common')
    ->where('name', '[-a-zA-Z0-9]+')
    ->name('img.icon');

// ─── Auth pages (HTML) ────────────────────────────────────────────────────────

Route::get('/auth/login',    [PageController::class, 'loginPage'])->name('auth.login.page');
Route::get('/auth/register', [PageController::class, 'registerPage'])->name('auth.register.page');
Route::get('/auth/password', [PageController::class, 'passwordPage'])->name('auth.password.page');
Route::get('/auth/otp',      [PageController::class, 'otpPage'])->name('auth.otp.page');
Route::get('/auth/logout',   [LogoutController::class, 'logout'])->name('auth.logout.get');
Route::get('/my',            [PageController::class, 'dashboard'])->name('my.dashboard');
Route::get('/my/verify',     [VerifyController::class, 'verify'])->name('auth.verify');
Route::get('/my/password', [PageController::class, 'passwordPage'])->name('my.pick-new-password.page');

// ─── Auth API endpoints ───────────────────────────────────────────────────────

// Login — 5 attempts / min, keyed by IP + username
Route::middleware('throttle:auth.login')->group(function () {
    Route::post('/auth/login', [LoginController::class, 'login'])->name('auth.login');
    Route::post('/api/passkeys/authenticate', [PasskeyLoginController::class, 'authenticate'])->name('passkeys.authenticate');
});

// Passkey authentication options (generates WebAuthn challenge, no throttle needed — no credentials submitted)
Route::get('/passkeys/authentication-options', GeneratePasskeyAuthenticationOptionsController::class)->name('passkeys.authentication_options');

// Register — 10 attempts / hour per IP
Route::middleware('throttle:auth.register')->group(function () {
    Route::post('/auth/register', [RegisterController::class, 'register'])->name('auth.register');
});

// Logout (no throttle needed)
Route::post('/auth/logout', [LogoutController::class, 'logout'])->name('auth.logout');

// Password recovery, reset, and 2FA OTP deactivation — 5 attempts / hour per IP
Route::middleware('throttle:auth.sensitive')->group(function () {
    Route::post('/auth/password/request', [PasswordController::class, 'requestReset'])->name('auth.password.request');
    Route::post('/auth/password/reset',   [PasswordController::class, 'reset'])->name('auth.password.reset');
    Route::post('/auth/otp',              [OtpController::class, 'requestDeactivation'])->name('auth.otp.deactivate');
});

// ─── App pages (HTML shells) ──────────────────────────────────────────────────

Route::get('/my/app/desktop', function () {
    return redirect()->route('app.file-manager', ['folder' => 'desktop']);
})->name('app.desktop');

Route::middleware('auth')->group(function () {
    Route::get('/my/app/file-manager', [PageController::class, 'fileManager'])->name('app.file-manager');
    Route::get('/my/app/contact',  [PageController::class, 'contact'])->name('app.contact');
    Route::get('/my/app/diary',    [PageController::class, 'diary'])->name('app.diary');
    Route::get('/my/app/message',  [PageController::class, 'message'])->name('app.message');
    Route::get('/my/app/settings',                [PageController::class, 'settings'])->name('app.settings');
    Route::get('/my/app/settings/account',        [PageController::class, 'settingsAccount'])->name('app.settings.account');
    Route::get('/my/app/settings/app',            [PageController::class, 'settingsApp'])->name('app.settings.app');
    Route::get('/my/app/settings/customize',      [PageController::class, 'settingsCustomize'])->name('app.settings.customize');
    Route::get('/my/app/settings/security',       [PageController::class, 'settingsSecurity'])->name('app.settings.security');
    Route::get('/my/app/settings/password',       [PageController::class, 'settingsPassword'])->name('app.settings.password');
    Route::get('/my/app/settings/2fa',            [PageController::class, 'settingsTwofa'])->name('app.settings.2fa');
    Route::get('/my/app/settings/language',       [PageController::class, 'settingsLanguage'])->name('app.settings.language');
    Route::get('/my/app/settings/delete-account', [PageController::class, 'settingsDeleteAccount'])->name('app.settings.delete-account');
    Route::get('/my/app/settings/export-data',    [PageController::class, 'settingsExportData'])->name('app.settings.export-data');
    Route::get('/my/app/settings/passkeys',       [PageController::class, 'settingsPasskeys'])->name('app.settings.passkeys');
    Route::get('/my/app/share',    [PageController::class, 'share'])->name('app.share');
    Route::get('/my/app/timetable', [PageController::class, 'timetable'])->name('app.timetable');
    Route::get('/my/app/trash',     [PageController::class, 'trash'])->name('app.trash');
    Route::get('/my/app/reader/{type}/{id}', [PageController::class, 'reader'])->name('app.reader')
        ->where('type', 'notebook|file|diary|contact')
        ->where('id', '[0-9]+');
    Route::get('/my/app/writer/{folder?}', [PageController::class, 'writer'])->name('app.writer');
    Route::get('/my/app/project',  [PageController::class, 'project'])->name('app.project');
});

// ─── Passkey management (authenticated) ──────────────────────────────────────

Route::middleware('auth')->group(function () {
    Route::get('/api/passkeys',                    [PasskeyController::class, 'index'])->name('passkeys.index');
    Route::get('/api/passkeys/register-options',   [PasskeyController::class, 'registerOptions'])->name('passkeys.register_options');
    Route::post('/api/passkeys/register',          [PasskeyController::class, 'store'])->name('passkeys.register');
    Route::delete('/api/passkeys/{id}',            [PasskeyController::class, 'destroy'])->name('passkeys.destroy')
        ->where('id', '[0-9]+');
});

// ─── Data export ──────────────────────────────────────────────────────────────

Route::middleware('auth')->group(function () {
    Route::post('/api/export/request', [DataExportController::class, 'request'])->name('export.request');
    Route::get('/my/export/{token}',   [DataExportController::class, 'showDownload'])->name('export.download');
    Route::post('/my/export/{token}',  [DataExportController::class, 'download'])->name('export.download.post');
});


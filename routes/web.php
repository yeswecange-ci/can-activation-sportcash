<?php

use App\Http\Controllers\Auth\AdminAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin/login');
});

// Route publique - Scan QR Code (tracking + redirect WhatsApp)
Route::get('/qr/{code}', [\App\Http\Controllers\Admin\QrCodeController::class, 'scan'])
    ->name('qr.scan');

// Routes Admin - Login
Route::prefix('admin')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('admin.login.post');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

    // Routes protégées par le middleware admin
    Route::middleware(['admin'])->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])
            ->name('admin.dashboard');

        // Routes Villages
        Route::resource('villages', \App\Http\Controllers\Admin\VillageController::class)
            ->names([
                'index'   => 'admin.villages.index',
                'create'  => 'admin.villages.create',
                'store'   => 'admin.villages.store',
                'show'    => 'admin.villages.show',
                'edit'    => 'admin.villages.edit',
                'update'  => 'admin.villages.update',
                'destroy' => 'admin.villages.destroy',
            ]);

        // Routes Partenaires
        Route::resource('partners', \App\Http\Controllers\Admin\PartnerController::class)
            ->names([
                'index'   => 'admin.partners.index',
                'create'  => 'admin.partners.create',
                'store'   => 'admin.partners.store',
                'show'    => 'admin.partners.show',
                'edit'    => 'admin.partners.edit',
                'update'  => 'admin.partners.update',
                'destroy' => 'admin.partners.destroy',
            ]);

        // Routes Matchs
        Route::resource('matches', \App\Http\Controllers\Admin\MatchController::class)
            ->names([
                'index'   => 'admin.matches.index',
                'create'  => 'admin.matches.create',
                'store'   => 'admin.matches.store',
                'show'    => 'admin.matches.show',
                'edit'    => 'admin.matches.edit',
                'update'  => 'admin.matches.update',
                'destroy' => 'admin.matches.destroy',
            ]);

        // Routes Users (Joueurs)
        Route::get('users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('admin.users.index');
        Route::get('users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'show'])->name('admin.users.show');
        Route::delete('users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('admin.users.destroy');

        // Routes Prizes (Lots)
        Route::resource('prizes', \App\Http\Controllers\Admin\PrizeController::class)
            ->names([
                'index'   => 'admin.prizes.index',
                'create'  => 'admin.prizes.create',
                'store'   => 'admin.prizes.store',
                'show'    => 'admin.prizes.show',
                'edit'    => 'admin.prizes.edit',
                'update'  => 'admin.prizes.update',
                'destroy' => 'admin.prizes.destroy',
            ]);

        // Routes QR Codes
        Route::resource('qrcodes', \App\Http\Controllers\Admin\QrCodeController::class)
            ->names([
                'index'   => 'admin.qrcodes.index',
                'create'  => 'admin.qrcodes.create',
                'store'   => 'admin.qrcodes.store',
                'show'    => 'admin.qrcodes.show',
                'edit'    => 'admin.qrcodes.edit',
                'update'  => 'admin.qrcodes.update',
                'destroy' => 'admin.qrcodes.destroy',
            ]);

        // Route de téléchargement
        Route::get('qrcodes/{qrcode}/download', [\App\Http\Controllers\Admin\QrCodeController::class, 'download'])
            ->name('admin.qrcodes.download');

        // Routes Pronostics
        Route::get('pronostics/stats', [\App\Http\Controllers\Admin\PronosticController::class, 'stats'])
            ->name('admin.pronostics.stats');
        Route::get('pronostics', [\App\Http\Controllers\Admin\PronosticController::class, 'index'])
            ->name('admin.pronostics.index');
        Route::get('pronostics/{pronostic}', [\App\Http\Controllers\Admin\PronosticController::class, 'show'])
            ->name('admin.pronostics.show');
        Route::delete('pronostics/{pronostic}', [\App\Http\Controllers\Admin\PronosticController::class, 'destroy'])
            ->name('admin.pronostics.destroy');
        Route::get('matches/{match}/pronostics', [\App\Http\Controllers\Admin\PronosticController::class, 'byMatch'])
            ->name('admin.pronostics.by-match');

        Route::post('/matches/{match}/evaluate-pronostics', [\App\Http\Controllers\Admin\PronosticController::class, 'evaluateMatch'])
            ->name('admin.matches.evaluate');
        Route::post('/pronostics/reevaluate-all', [\App\Http\Controllers\Admin\PronosticController::class, 'reevaluateAll'])
            ->name('admin.pronostics.reevaluate-all');

        // Routes Templates de Messages
        Route::resource('templates', \App\Http\Controllers\Admin\MessageTemplateController::class)
            ->names([
                'index'   => 'admin.templates.index',
                'create'  => 'admin.templates.create',
                'store'   => 'admin.templates.store',
                'show'    => 'admin.templates.show',
                'edit'    => 'admin.templates.edit',
                'update'  => 'admin.templates.update',
                'destroy' => 'admin.templates.destroy',
            ]);
        Route::post('templates/{template}/duplicate', [\App\Http\Controllers\Admin\MessageTemplateController::class, 'duplicate'])
            ->name('admin.templates.duplicate');
        Route::get('templates/{template}/preview', [\App\Http\Controllers\Admin\MessageTemplateController::class, 'preview'])
            ->name('admin.templates.preview');

        // Routes Campagnes
        Route::resource('campaigns', \App\Http\Controllers\Admin\CampaignController::class)
            ->names([
                'index'   => 'admin.campaigns.index',
                'create'  => 'admin.campaigns.create',
                'store'   => 'admin.campaigns.store',
                'show'    => 'admin.campaigns.show',
                'edit'    => 'admin.campaigns.edit',
                'update'  => 'admin.campaigns.update',
                'destroy' => 'admin.campaigns.destroy',
            ]);
        Route::get('campaigns/{campaign}/confirm-send', [\App\Http\Controllers\Admin\CampaignController::class, 'confirmSend'])
            ->name('admin.campaigns.confirm-send');
        Route::post('campaigns/{campaign}/send', [\App\Http\Controllers\Admin\CampaignController::class, 'send'])
            ->name('admin.campaigns.send');
        Route::post('campaigns/{campaign}/test', [\App\Http\Controllers\Admin\CampaignController::class, 'test'])
            ->name('admin.campaigns.test');

        // Routes Classement
        Route::get('leaderboard', [\App\Http\Controllers\Admin\LeaderboardController::class, 'index'])
            ->name('admin.leaderboard');
        Route::get('leaderboard/village/{village}', [\App\Http\Controllers\Admin\LeaderboardController::class, 'village'])
            ->name('admin.leaderboard.village');

        // Routes Analytics
        Route::get('analytics', [\App\Http\Controllers\Admin\AnalyticsController::class, 'index'])
            ->name('admin.analytics');
        Route::get('analytics/export/users', [\App\Http\Controllers\Admin\AnalyticsController::class, 'exportUsers'])
            ->name('admin.analytics.export.users');
        Route::get('analytics/export/pronostics', [\App\Http\Controllers\Admin\AnalyticsController::class, 'exportPronostics'])
            ->name('admin.analytics.export.pronostics');
    });
});

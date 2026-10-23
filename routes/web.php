<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MonthlyPlanController;
use App\Http\Controllers\PerformanceController;
use App\Http\Controllers\QuarterlyPerformanceController;
use App\Http\Controllers\StrategyController;
use App\Http\Controllers\TelegramController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\WeeklyPlanController;
use Illuminate\Support\Facades\Route;

// Public Telegram Webhook Endpoint
Route::post('/telegram/webhook', [TelegramController::class, 'webhook'])->name('telegram.webhook');
Route::any('/api/cron/run', [TelegramController::class, 'runAutomation'])->name('cron.run');

// Guest Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/', [DashboardController::class, 'overview'])->name('dashboard');
    Route::get('/overview', [DashboardController::class, 'overview'])->name('overview');

    // Dashboards & Executive Views
    Route::get('/management/dashboard', [DashboardController::class, 'gm'])->name('dashboard.gm');
    Route::get('/management/plans', [DashboardController::class, 'gm'])->name('management.plans');
    Route::post('/management/remind', [DashboardController::class, 'remind'])->name('management.remind');
    Route::get('/management/remind', fn() => redirect()->route('dashboard.gm'));
    Route::match(['GET', 'POST'], '/management/run-telegram-check', [DashboardController::class, 'runTelegramCheck'])->name('management.run_telegram_check');
    Route::get('/department/dashboard', [DashboardController::class, 'department'])->name('dashboard.department');

    // Strategy & Monthly Activation
    Route::get('/strategy/annual', [StrategyController::class, 'annualGoals'])->name('strategy.annual');
    Route::get('/strategy/activation', [StrategyController::class, 'monthlyActivation'])->name('strategy.activation');
    Route::post('/strategy/activation/toggle', [StrategyController::class, 'toggleActivation'])->name('strategy.activation.toggle');
    Route::post('/strategy/activation/bulk', [StrategyController::class, 'bulkActivation'])->name('strategy.activation.bulk');

    // Monthly Planning
    Route::get('/monthly/plans', [MonthlyPlanController::class, 'index'])->name('monthly.plans');
    Route::post('/monthly/plans', [MonthlyPlanController::class, 'store'])->name('monthly.plans.store');
    Route::post('/monthly/submit-package', [MonthlyPlanController::class, 'submitPackage'])->name('monthly.plans.submit_package');
    Route::post('/monthly/feedback', [MonthlyPlanController::class, 'saveFeedback'])->name('monthly.feedback');
    Route::delete('/monthly/plans/{id}', [MonthlyPlanController::class, 'destroy'])->name('monthly.plans.destroy');

    // Weekly Planning
    Route::get('/weekly/plans', [WeeklyPlanController::class, 'index'])->name('weekly.plans');
    Route::get('/weekly/tasks', [WeeklyPlanController::class, 'index'])->name('weekly.tasks');
    Route::post('/weekly/tasks', [WeeklyPlanController::class, 'storeTask'])->name('weekly.tasks.store');
    Route::post('/weekly/plans/submit', [WeeklyPlanController::class, 'submitPlan'])->name('weekly.plans.submit');
    Route::delete('/weekly/tasks/{id}', [WeeklyPlanController::class, 'destroyTask'])->name('weekly.tasks.destroy');

    // Weekly Performance Execution
    Route::get('/weekly/performance', [PerformanceController::class, 'execution'])->name('weekly.performance');
    Route::post('/weekly/performance/evaluate', [PerformanceController::class, 'saveEvaluation'])->name('weekly.performance.evaluate');
    Route::get('/weekly/achievements', fn() => redirect()->route('performance.achievements'));
    Route::post('/weekly/achievements', [PerformanceController::class, 'saveAchievement'])->name('weekly.achievements.store');
    Route::delete('/weekly/achievements/{id}', [PerformanceController::class, 'deleteAchievement'])->name('weekly.achievements.destroy');
    Route::get('/weekly/challenges', fn() => redirect()->route('performance.challenges'));
    Route::post('/weekly/challenges', [PerformanceController::class, 'saveChallenge'])->name('weekly.challenges.store');
    Route::delete('/weekly/challenges/{id}', [PerformanceController::class, 'deleteChallenge'])->name('weekly.challenges.destroy');
    Route::post('/weekly/submit', [PerformanceController::class, 'submitReport'])->name('weekly.report.submit');
    Route::post('/weekly/feedback', [PerformanceController::class, 'saveWeeklyFeedback'])->name('weekly.feedback');

    // Bulk Quarterly Performance Submission Module (All 3 Months)
    Route::prefix('performance/quarterly')->name('performance.quarterly')->group(function () {
        Route::get('/', [QuarterlyPerformanceController::class, 'index']);
        Route::post('/save-evaluation', [QuarterlyPerformanceController::class, 'saveEvaluation'])->name('.save_evaluation');
        Route::post('/submit', [QuarterlyPerformanceController::class, 'submitQuarter'])->name('.submit');
        Route::post('/feedback', [QuarterlyPerformanceController::class, 'saveFeedback'])->name('.feedback');
        Route::get('/export', [QuarterlyPerformanceController::class, 'exportExcel'])->name('.export');
    });

    // Vouchers (Official Printable & Document Layouts)
    Route::get('/monthly/voucher', [VoucherController::class, 'monthly'])->name('monthly.voucher');
    Route::get('/weekly/voucher', [VoucherController::class, 'weekly'])->name('weekly.voucher');
    Route::get('/quarterly/voucher', [VoucherController::class, 'quarterly'])->name('quarterly.voucher');

    // Scorecards & Organization Performance
    Route::get('/performance/weekly', [PerformanceController::class, 'weeklyScorecard'])->name('performance.weekly');
    Route::get('/performance/monthly', [PerformanceController::class, 'monthlyScorecard'])->name('performance.monthly');
    Route::get('/performance/not-done', [PerformanceController::class, 'notDoneAnalysis'])->name('performance.not_done');
    Route::get('/performance/achievements', [PerformanceController::class, 'achievementsFeed'])->name('performance.achievements');
    Route::get('/performance/challenges', [PerformanceController::class, 'challengesFeed'])->name('performance.challenges');

    // Telegram Bot Integration & Live Status (Strictly Super Admin & IT Admin)
    Route::middleware('role:SUPER_ADMIN,IT_ADMIN')->prefix('telegram')->name('telegram.')->group(function () {
        Route::get('/status', [TelegramController::class, 'status'])->name('status');
        Route::post('/test-connection', [TelegramController::class, 'testConnection'])->name('test');
        Route::post('/update-token', [TelegramController::class, 'updateToken'])->name('update_token');
        Route::post('/send-test', [TelegramController::class, 'sendTestMessage'])->name('send_test');
        Route::get('/webhook/info', [TelegramController::class, 'webhookInfo'])->name('webhook.info');
        Route::post('/webhook/set', [TelegramController::class, 'setWebhook'])->name('webhook.set');
        Route::post('/webhook/delete', [TelegramController::class, 'deleteWebhook'])->name('webhook.delete');
        Route::post('/poll', [TelegramController::class, 'poll'])->name('poll');
        Route::any('/run-automation', [TelegramController::class, 'runAutomation'])->name('run_automation');
    });

    // Executive & Governance Reports (Accessible to Super Admin, IT Admin, GM, and HR)
    Route::middleware('role:SUPER_ADMIN,IT_ADMIN,GM_MANAGEMENT,GM,GENERAL_MANAGER,HR')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/reports', [AdminController::class, 'reports'])->name('reports');
        Route::get('/reports/export', [AdminController::class, 'exportExcel'])->name('reports.export');
    });

    // Super Admin Area (Strictly Super Administrator)
    Route::middleware('role:SUPER_ADMIN')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
        Route::put('/users/{id}', [AdminController::class, 'updateUser'])->name('users.update');
        Route::delete('/users/{id}', [AdminController::class, 'destroyUser'])->name('users.destroy');
        Route::get('/departments', [AdminController::class, 'departments'])->name('departments');
        Route::post('/departments', [AdminController::class, 'storeDepartment'])->name('departments.store');
        Route::put('/departments/{id}', [AdminController::class, 'updateDepartment'])->name('departments.update');
        Route::delete('/departments/{id}', [AdminController::class, 'destroyDepartment'])->name('departments.destroy');
        Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
        Route::post('/settings', [AdminController::class, 'updateSettings'])->name('settings.update');
        Route::get('/audit-logs', [AdminController::class, 'auditLogs'])->name('audit');
    });
});

<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MonthlyPlanController;
use App\Http\Controllers\PerformanceController;
use App\Http\Controllers\StrategyController;
use App\Http\Controllers\WeeklyPlanController;
use Illuminate\Support\Facades\Route;

// Guest Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Dashboards
    Route::get('/management/dashboard', [DashboardController::class, 'gm'])->name('dashboard.gm');
    Route::get('/department/dashboard', [DashboardController::class, 'department'])->name('dashboard.department');

    // Strategy & Monthly Activation
    Route::get('/strategy/annual', [StrategyController::class, 'annualGoals'])->name('strategy.annual');
    Route::get('/strategy/activation', [StrategyController::class, 'monthlyActivation'])->name('strategy.activation');
    Route::post('/strategy/activation/toggle', [StrategyController::class, 'toggleActivation'])->name('strategy.activation.toggle');

    // Monthly Planning
    Route::get('/monthly/plans', [MonthlyPlanController::class, 'index'])->name('monthly.plans');
    Route::post('/monthly/plans', [MonthlyPlanController::class, 'store'])->name('monthly.plans.store');
    Route::delete('/monthly/plans/{id}', [MonthlyPlanController::class, 'destroy'])->name('monthly.plans.destroy');

    // Weekly Planning
    Route::get('/weekly/plans', [WeeklyPlanController::class, 'index'])->name('weekly.plans');
    Route::post('/weekly/tasks', [WeeklyPlanController::class, 'storeTask'])->name('weekly.tasks.store');
    Route::delete('/weekly/tasks/{id}', [WeeklyPlanController::class, 'destroyTask'])->name('weekly.tasks.destroy');

    // Weekly Performance Execution
    Route::get('/weekly/performance', [PerformanceController::class, 'execution'])->name('weekly.performance');
    Route::post('/weekly/performance/evaluate', [PerformanceController::class, 'saveEvaluation'])->name('weekly.performance.evaluate');
    Route::post('/weekly/achievements', [PerformanceController::class, 'saveAchievement'])->name('weekly.achievements.store');
    Route::delete('/weekly/achievements/{id}', [PerformanceController::class, 'deleteAchievement'])->name('weekly.achievements.destroy');
    Route::post('/weekly/challenges', [PerformanceController::class, 'saveChallenge'])->name('weekly.challenges.store');
    Route::delete('/weekly/challenges/{id}', [PerformanceController::class, 'deleteChallenge'])->name('weekly.challenges.destroy');
    Route::post('/weekly/submit', [PerformanceController::class, 'submitReport'])->name('weekly.report.submit');

    // Scorecards & Organization Performance
    Route::get('/performance/weekly', [PerformanceController::class, 'weeklyScorecard'])->name('performance.weekly');
    Route::get('/performance/monthly', [PerformanceController::class, 'monthlyScorecard'])->name('performance.monthly');
    Route::get('/performance/not-done', [PerformanceController::class, 'notDoneAnalysis'])->name('performance.not_done');
    Route::get('/performance/achievements', [PerformanceController::class, 'achievementsFeed'])->name('performance.achievements');
    Route::get('/performance/challenges', [PerformanceController::class, 'challengesFeed'])->name('performance.challenges');

    // Super Admin Area
    Route::middleware('role:SUPER_ADMIN')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
        Route::get('/departments', [AdminController::class, 'departments'])->name('departments');
        Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
        Route::post('/settings', [AdminController::class, 'updateSettings'])->name('settings.update');
        Route::get('/audit-logs', [AdminController::class, 'auditLogs'])->name('audit');
        Route::get('/reports', [AdminController::class, 'reports'])->name('reports');
    });
});

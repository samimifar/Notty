<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ContactController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/notes', [DashboardController::class, 'storeNote'])->name('notes.store');
    Route::delete('/notes/{note}', [DashboardController::class, 'deleteNote'])->name('notes.delete');
    Route::put('/notes/{note}', [DashboardController::class, 'updateNote'])->name('notes.update');

    // Tasks
    Route::get('/tasks', [DashboardController::class, 'tasks'])->name('tasks.list');
    Route::post('/tasks', [DashboardController::class, 'storeTask'])->name('tasks.store');
    Route::put('/tasks/{task}', [DashboardController::class, 'updateTask'])->name('tasks.update');
    Route::delete('/tasks/{task}', [DashboardController::class, 'deleteTask'])->name('tasks.delete');

    // Events
    Route::get('/events', [EventController::class, 'index'])->name('events.index');
    Route::get('/events/json', [EventController::class, 'listJson'])->name('events.json');
    Route::get('/events/list', [EventController::class, 'index'])->name('events.list');
    Route::post('/events', [EventController::class, 'store'])->name('events.store');
    Route::get('/events/week', [EventController::class, 'week'])->name('events.week');
    Route::post('/events/check-conflicts', [EventController::class, 'checkConflicts'])->name('events.check');
    Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');
    Route::put('/events/{event}', [EventController::class, 'update'])->name('events.update');
    Route::delete('/events/{event}', [EventController::class, 'destroy'])->name('events.delete');

    // Contacts
    Route::get('/contacts', [ContactController::class, 'index'])->name('contacts.index');

    Route::get('/dashboard/day', [DashboardController::class, 'day'])->name('dashboard.day');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

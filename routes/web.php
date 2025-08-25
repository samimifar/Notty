<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;


Route::get('/', fn() => redirect()->route('dashboard'))
    ->middleware('auth');
Route::get('/', fn() => redirect()->route('login'))
    ->withoutMiddleware('auth');

Route::middleware('auth')->group(function () {

    // Initial
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Dashboard
    Route::get('/dashboard/day', [DashboardController::class, 'day'])->name('dashboard.day');

    // Admin
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');

    // Notes
    Route::post('/notes', [NoteController::class, 'storeNote'])->name('notes.store');
    Route::delete('/notes/{note}', [NoteController::class, 'deleteNote'])->name('notes.delete');
    Route::put('/notes/{note}', [NoteController::class, 'updateNote'])->name('notes.update');

    // Tasks
    Route::get('/tasks', [TaskController::class, 'tasks'])->name('tasks.list');
    Route::post('/tasks', [TaskController::class, 'storeTask'])->name('tasks.store');
    Route::put('/tasks/{task}', [TaskController::class, 'updateTask'])->name('tasks.update');
    Route::delete('/tasks/{task}', [TaskController::class, 'deleteTask'])->name('tasks.delete');

    // Events
    Route::get('/events', [EventController::class, 'index'])->name('events.index');
    Route::get('/events/json', [EventController::class, 'listJson'])->name('events.json');
    Route::post('/events', [EventController::class, 'store'])->name('events.store');
    Route::get('/events/week', [EventController::class, 'week'])->name('events.week');
    Route::post('/events/check-conflicts', [EventController::class, 'checkConflicts'])->name('events.check');
    Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');
    Route::put('/events/{event}', [EventController::class, 'update'])->name('events.update');
    Route::delete('/events/{event}', [EventController::class, 'destroy'])->name('events.delete');
    Route::get('/events/{event}/eligible-invitees', [EventController::class, 'eligibleInvitees'])->name('events.eligible');
    Route::post('/events/{event}/invite', [EventController::class, 'invite'])->name('events.invite');
    Route::post('/events/{event}/invite/{invite}/accept', [EventController::class, 'acceptInvite'])->name('events.invite.accept');
    Route::post('/events/{event}/invite/{invite}/reject', [EventController::class, 'rejectInvite'])->name('events.invite.reject');

    // Contacts
    Route::get('/contacts', [ContactController::class, 'index'])->name('contacts.index');
    Route::get('/contacts/all', [ContactController::class, 'contacts'])->name('contacts.contacts');
    Route::get('/contacts/{contact}', [ContactController::class, 'show'])->name('contacts.show');
    Route::post('/contacts', [ContactController::class, 'store'])->name('contacts.store');
    Route::put('/contacts/{contact}', [ContactController::class, 'update'])->name('contacts.update');
    Route::delete('/contacts/{contact}', [ContactController::class, 'destroy'])->name('contacts.delete');

    // Groups
    Route::get('/groups', [GroupController::class, 'index'])->name('groups.index');
    Route::get('/groups/invites', [GroupController::class, 'invites'])->name('groups.invites');
    Route::post('/groups/invites/{invite}/accept', [GroupController::class, 'respondInvite'])->name('groups.invites.accept');
    Route::post('/groups/invites/{invite}/reject', [GroupController::class, 'respondInvite'])->name('groups.invites.reject');
    Route::get('/groups/{group}/members', [GroupController::class, 'members'])->name('groups.members');
    Route::delete('/groups/{group}/members/{user}', [GroupController::class, 'removeMember'])->name('groups.members.remove');
    Route::delete('/groups/{group}/leave', [GroupController::class, 'leave'])->name('groups.leave');
    Route::patch('/groups/{group}', [GroupController::class, 'update'])->name('groups.update');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';

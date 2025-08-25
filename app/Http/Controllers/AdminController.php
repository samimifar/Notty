<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Event;
use App\Models\Task;
use App\Models\Note;

class AdminController extends Controller
{
    public function index()
    {
        if (auth()->user()->role == "admin") {
            $stats = [
                'users'  => User::count(),
                'events' => Event::count(),
                'tasks'  => Task::count(),
                'notes'  => Note::count(),
            ];

            $perUser = User::query()
                ->withCount(['events', 'tasks', 'notes'])
                ->orderByDesc('events_count')
                ->paginate(20);

            return view('admin.index', compact('stats', 'perUser'));
        } else {
            abort(403);
        }
    }
}

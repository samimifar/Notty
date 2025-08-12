<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Note;
use App\Models\PublicEvent;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        $note = Note::where('user_id', Auth::id())
            ->whereMonth('date', $today->month)
            ->whereDay('date', $today->day)
            ->first();

        $publicEvent = PublicEvent::whereMonth('date', $today->month)
            ->whereDay('date', $today->day)
            ->first();

        return view('dashboard', [
            'note' => $note,
            'publicEvent' => $publicEvent,
            'today' => $today
        ]);
    }

    public function storeNote(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'text' => 'required|string|max:1000',
        ]);

        $c = Carbon::parse($data['date']);

        $note = Note::where('user_id', Auth::id())
            ->whereMonth('date', $c->month)
            ->whereDay('date', $c->day)
            ->first();

        if ($note) {
            $note->update(['text' => $data['text']]);
        } else {
            $note = Note::create([
                'user_id' => Auth::id(),
                'date' => $data['date'], // store full date; queries ignore year
                'text' => $data['text']
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Note saved successfully.',
                'note' => $note
            ]);
        }

        return back()->with('status', 'Note saved successfully.');
    }

    public function updateNote(Request $request, Note $note)
    {
        if ($note->user_id !== Auth::id()) {
            abort(403);
        }

        $data = $request->validate([
            'text' => 'required|string|max:1000',
        ]);

        $note->update([
            'text' => $data['text']
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Note updated successfully.',
                'note' => $note
            ]);
        }

        return back()->with('status', 'Note updated successfully.');
    }

    public function deleteNote(Note $note)
    {
        if ($note->user_id !== Auth::id()) {
            abort(403);
        }
        $note->delete();
        $request = request();
        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Note deleted successfully.'
            ]);
        }
        return back()->with('success', 'Note deleted.');
    }

     public function day(Request $request)
    {
        $request->validate([
            'date' => ['required','date'],
        ]);
        $date = $request->input('date');

        $c = Carbon::parse($date);
        $note = Note::where('user_id', Auth::id())
            ->whereMonth('date', $c->month)
            ->whereDay('date', $c->day)
            ->first(['id','text','date','user_id']);

        $publicEvent = PublicEvent::whereMonth('date', $c->month)
            ->whereDay('date', $c->day)
            ->first(['id','name','date']);

        return response()->json([
            'date' => $date,
            'note' => $note,
            'publicEvent' => $publicEvent,
        ]);
    }
    public function tasks()
    {
        $tasks = \App\Models\Task::where('user_id', Auth::id())
            ->orderBy('deadline', 'asc')
            ->get()
            ->map(function ($task) {
                $task->time_left = $this->formatTimeLeft($task->deadline);
                return $task;
            });

        return response()->json($tasks);
    }

    public function storeTask(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'deadline' => 'required|date',
        ]);

        $data['user_id'] = Auth::id();
        $data['status'] = 0; // All new tasks start as pending

        $task = \App\Models\Task::create($data);
        $task->time_left = $this->formatTimeLeft($task->deadline);

        return response()->json([
            'status' => 'success',
            'message' => 'Task added successfully.',
            'task' => $task
        ]);
    }

    public function updateTask(Request $request, \App\Models\Task $task)
    {
        if ($task->user_id !== Auth::id()) {
            abort(403);
        }

        // Quick status toggle path
        if ($request->has('status') && count($request->all()) === 1) {
            $data = $request->validate([
                'status' => 'boolean'
            ]);
            $task->update(['status' => $data['status']]);
            $task->time_left = $this->formatTimeLeft($task->deadline);
            return response()->json([
                'status' => 'success',
                'message' => 'Task status updated successfully.',
                'task' => $task
            ]);
        }

        // Normal edit (status is optional)
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'deadline' => 'required|date',
            'status' => 'sometimes|boolean'
        ]);

        $task->update($data);
        $task->time_left = $this->formatTimeLeft($task->deadline);

        return response()->json([
            'status' => 'success',
            'message' => 'Task updated successfully.',
            'task' => $task
        ]);
    }

    public function completeTask(\App\Models\Task $task)
    {
        if ($task->user_id !== Auth::id()) {
            abort(403);
        }
        $task->status = 1;
        $task->save();
        return response()->json([
            'status' => 'success',
            'message' => 'Task marked as completed.',
            'task' => $task
        ]);
    }

    public function deleteTask(\App\Models\Task $task)
    {
        if ($task->user_id !== Auth::id()) {
            abort(403);
        }
        $task->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Task deleted successfully.'
        ]);
    }

    private function formatTimeLeft($deadline)
    {
        $now = Carbon::now();
        $deadline = Carbon::parse($deadline);

        if ($now->greaterThan($deadline)) {
            return 'زمان گذشته';
        }

        $diffInMinutes = $now->diffInMinutes($deadline);
        $diffInHours = $now->diffInHours($deadline);
        $diffInDays = $now->diffInDays($deadline);
        $diffInWeeks = $now->diffInWeeks($deadline);
        $diffInMonths = $now->diffInMonths($deadline);
        $diffInYears = $now->diffInYears($deadline);

        if ($diffInYears > 0) {
            return $diffInYears . ' سال مانده';
        } elseif ($diffInMonths > 0) {
            return $diffInMonths . ' ماه مانده';
        } elseif ($diffInWeeks > 0) {
            return $diffInWeeks . ' هفته مانده';
        } elseif ($diffInDays > 0) {
            return $diffInDays . ' روز مانده';
        } elseif ($diffInHours > 0) {
            return $diffInHours . ' ساعت مانده';
        } else {
            return $diffInMinutes . ' دقیقه مانده';
        }
    }
}

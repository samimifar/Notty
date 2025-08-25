<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Task;
use Carbon\Carbon;


class TaskController extends Controller
{
    public function tasks()
    {
        $tasks = Task::where('user_id', Auth::id())
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
        $data['status'] = 0; 

        $task = Task::create($data);
        $task->time_left = $this->formatTimeLeft($task->deadline);

        return response()->json([
            'status' => 'success',
            'message' => 'Task added successfully.',
            'task' => $task
        ]);
    }

    public function updateTask(Request $request, Task $task)
    {
        if ($task->user_id !== Auth::id()) {
            abort(403);
        }

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

    public function completeTask(Task $task)
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

    public function deleteTask(Task $task)
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

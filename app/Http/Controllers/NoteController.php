<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Note;
use Carbon\Carbon;

class NoteController extends Controller
{
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
}

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
        return view('dashboard');
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
    
}

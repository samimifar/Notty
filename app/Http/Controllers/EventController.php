<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class EventController extends Controller
{
    public function index()
    {
        $events = Event::where('user_id', Auth::id())
            ->orderBy('date_time', 'asc')
            ->get();

        return response()->json($events);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'date_time'   => 'required|date',
            'duration'    => 'required|integer|min:1|max:10080',
            'cycle'       => 'required|string|in:once,daily,weekly,monthly,yearly'
        ]);

        $event = Event::create([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'date_time'   => $validated['date_time'],
            'duration'    => (int) $validated['duration'],
            'cycle'       => $validated['cycle'],
            'user_id'     => Auth::id(),
        ]);

        return response()->json($event, 201);
    }

    public function show(Event $event)
    {
        if ($event->user_id !== Auth::id()) {
            abort(403);
        }
        return response()->json($event);
    }

    public function update(Request $request, Event $event)
    {
        if ($event->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'date_time'   => 'required|date',
            'duration'    => 'required|integer|min:1|max:10080',
            'cycle'       => 'required|string|in:once,daily,weekly,monthly,yearly'
        ]);

        $event->update([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'date_time'   => $validated['date_time'],
            'cycle'       => $validated['cycle'],
            'duration'    => (int) $validated['duration'],
        ]);

        return response()->json($event);
    }

    public function week(Request $request)
    {
        $start = Carbon::parse($request->query('start', Carbon::now()->startOfWeek(Carbon::SATURDAY)))->startOfDay();
        $end   = (clone $start)->addDays(6)->endOfDay();
        $uid   = Auth::id();

        // Fetch relevant events (own events for now)
        $events = Event::where('user_id', $uid)
            ->where(function($q) use ($start, $end) {
                $q->where(function($qq) use ($start, $end){
                    $qq->where('cycle', 'once')->whereBetween('date_time', [$start, $end]);
                })->orWhere(function($qq) use ($end){
                    $qq->whereIn('cycle', ['daily','weekly','monthly','yearly'])
                       ->where('date_time', '<=', $end);
                });
            })
            ->orderBy('date_time', 'asc')
            ->get();

        // Prepare days bucket
        $days = [];
        for ($i=0; $i<7; $i++) {
            $d = (clone $start)->addDays($i);
            $days[$d->toDateString()] = [ 'date' => $d->toDateString(), 'events' => [] ];
        }

        foreach ($events as $ev) {
            $base = Carbon::parse($ev->date_time);
            $baseStartDay = $base->copy()->startOfDay();
            $duration = max(1, (int)($ev->duration ?? 60)); // minutes

            switch ($ev->cycle) {
                case 'once':
                    $d = $base->toDateString();
                    if (isset($days[$d])) {
                        self::pushOccurrence($days[$d]['events'], $ev, $base, $duration);
                    }
                    break;
                case 'daily':
                    for ($i=0; $i<7; $i++) {
                        $d = (clone $start)->addDays($i);
                        // daily from the day it starts
                        if ($d->greaterThanOrEqualTo($baseStartDay)) {
                            self::pushOccurrence($days[$d->toDateString()]['events'], $ev, $d->copy()->setTime($base->hour, $base->minute), $duration);
                        }
                    }
                    break;
                case 'weekly':
                    for ($i=0; $i<7; $i++) {
                        $d = (clone $start)->addDays($i);
                        if ((int)$d->dayOfWeek === (int)$base->dayOfWeek && $d->greaterThanOrEqualTo($baseStartDay)) {
                            self::pushOccurrence($days[$d->toDateString()]['events'], $ev, $d->copy()->setTime($base->hour, $base->minute), $duration);
                        }
                    }
                    break;
                case 'monthly':
                    for ($i=0; $i<7; $i++) {
                        $d = (clone $start)->addDays($i);
                        if ((int)$d->day === (int)$base->day && $d->greaterThanOrEqualTo($baseStartDay)) {
                            self::pushOccurrence($days[$d->toDateString()]['events'], $ev, $d->copy()->setTime($base->hour, $base->minute), $duration);
                        }
                    }
                    break;
                case 'yearly':
                    for ($i=0; $i<7; $i++) {
                        $d = (clone $start)->addDays($i);
                        if ((int)$d->day === (int)$base->day && (int)$d->month === (int)$base->month && $d->greaterThanOrEqualTo($baseStartDay)) {
                            self::pushOccurrence($days[$d->toDateString()]['events'], $ev, $d->copy()->setTime($base->hour, $base->minute), $duration);
                        }
                    }
                    break;
            }
        }

        // Sort events inside each day by time
        foreach ($days as &$bucket) {
            usort($bucket['events'], function($a,$b){ return strcmp($a['start'], $b['start']); });
        }

        return response()->json([
            'start' => $start->toDateString(),
            'end'   => $end->toDateString(),
            'days'  => array_values($days),
        ]);
    }

    private static function pushOccurrence(array &$list, Event $ev, Carbon $startAt, int $duration)
    {
        $endAt = (clone $startAt)->addMinutes($duration);
        $list[] = [
            'id'     => $ev->id,
            'title'  => $ev->name,
            'start'  => $startAt->format('H:i'),
            'end'    => $endAt->format('H:i'),
            'cycle'  => $ev->cycle,
            'color'  => null,
        ];
    }

    public function destroy(Event $event)
    {
        if ($event->user_id !== Auth::id()) {
            abort(403);
        }
        $event->delete();

        return response()->json(null, 204);
    }
}

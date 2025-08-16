<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class EventController extends Controller
{
    public function json(Request $request)
    {
        $events = Event::where('user_id', Auth::id())
            ->orderBy('date_time', 'asc')
            ->get();

        return response()->json($events);
    }

    public function index()
    {
        $uid = Auth::id();

        $events = Event::where('user_id', $uid)
            ->orderBy('date_time', 'desc')
            ->get()
            ->map(function ($ev) {
                $start = $ev->date_time instanceof Carbon
                    ? $ev->date_time->copy()
                    : Carbon::parse($ev->date_time);
                $end = (clone $start)->addMinutes(max(1, (int) ($ev->duration ?? 0)));

                return [
                    'id'          => $ev->id,
                    'title'       => $ev->name,
                    'description' => $ev->description,
                    'cycle'       => $ev->cycle,
                    'start'       => $start->format('Y-m-d H:i'),
                    'end'         => $end->format('Y-m-d H:i'),
                ];
            });

        return view('events.index', ['events' => $events]);
    }

    public function listJson(Request $request)
    {
        return $this->json($request);
    }

    /**
     * Check if two time ranges overlap.
     */
    private static function rangesOverlap(Carbon $aStart, Carbon $aEnd, Carbon $bStart, Carbon $bEnd): bool
    {
        return $aStart->lt($bEnd) && $aEnd->gt($bStart);
    }

    /**
     * Find conflicts for a candidate event against user's existing events in [from,to].
     * Optionally exclude an event id (useful in update).
     */
    private function findConflicts(Event $candidate, int $userId, Carbon $from, Carbon $to, ?int $excludeId = null): array
    {
        $conflicts = [];

        // Fetch candidate occurrences (uses Event model helper)
        $candOccs = $candidate->generateOccurrences($from, $to);

        if (empty($candOccs)) {
            return $conflicts;
        }

        $events = Event::where('user_id', $userId)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->where('date_time', '<=', $to)
            ->orderBy('date_time', 'asc')
            ->get();

        foreach ($events as $ev) {
            $evOccs = $ev->generateOccurrences($from, $to);
            foreach ($evOccs as $eo) {
                foreach ($candOccs as $co) {
                    if (self::rangesOverlap($co['start'], $co['end'], $eo['start'], $eo['end'])) {
                        $conflicts[] = [
                            'event_id'   => $ev->id,
                            'event_name' => $ev->name,
                            'their'      => [
                                'start' => $eo['start']->toDateTimeString(),
                                'end'   => $eo['end']->toDateTimeString(),
                            ],
                            'yours'      => [
                                'start' => $co['start']->toDateTimeString(),
                                'end'   => $co['end']->toDateTimeString(),
                            ],
                        ];
                    }
                }
            }
        }

        return $conflicts;
    }

    /**
     * Convert internal conflict structure to a flat list the UI expects.
     */
    private function normalizeConflicts(array $items): array
    {
        return array_map(function ($c) {
            $start = Carbon::parse($c['their']['start']);
            $end   = Carbon::parse($c['their']['end']);
            return [
                'id'       => $c['event_id'],
                'title'    => $c['event_name'],
                'start'    => $start->format('Y-m-d H:i'),
                'end'      => $end->format('Y-m-d H:i'),
                'duration' => $start->diffInMinutes($end),
            ];
        }, $items);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'date_time'   => 'required|date',
            'duration'    => 'required|integer|min:1|max:10080',
            'cycle'       => 'required|string|in:once,daily,weekly,monthly,yearly',
            'force'       => 'sometimes|boolean',
        ]);

        $uid  = Auth::id();
        $from = Carbon::now();
        $to   = (clone $from)->addYear();

        // Build a transient candidate for conflict check
        $candidate = new Event([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'date_time'   => Carbon::parse($validated['date_time']),
            'duration'    => (int) $validated['duration'],
            'cycle'       => $validated['cycle'],
            'user_id'     => $uid,
        ]);

        $conflicts = $this->findConflicts($candidate, $uid, $from, $to);
        if (!$request->boolean('force') && !empty($conflicts)) {
            return response()->json([
                'conflict'  => true,
                'conflicts' => $this->normalizeConflicts($conflicts),
            ], 409);
        }

        $event = Event::create([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'date_time'   => $validated['date_time'],
            'duration'    => (int) $validated['duration'],
            'cycle'       => $validated['cycle'],
            'user_id'     => $uid,
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
            'cycle'       => 'required|string|in:once,daily,weekly,monthly,yearly',
            'force'       => 'sometimes|boolean',
        ]);

        $uid  = Auth::id();
        $from = Carbon::now();
        $to   = (clone $from)->addYear();

        $candidate = new Event([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'date_time'   => Carbon::parse($validated['date_time']),
            'duration'    => (int) $validated['duration'],
            'cycle'       => $validated['cycle'],
            'user_id'     => $uid,
        ]);

        $conflicts = $this->findConflicts($candidate, $uid, $from, $to, $event->id);
        if (!$request->boolean('force') && !empty($conflicts)) {
            return response()->json([
                'conflict'  => true,
                'conflicts' => $this->normalizeConflicts($conflicts),
            ], 409);
        }

        $event->update([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'date_time'   => $validated['date_time'],
            'cycle'       => $validated['cycle'],
            'duration'    => (int) $validated['duration'],
        ]);

        return response()->json($event);
    }

    public function checkConflicts(Request $request)
    {
        $validated = $request->validate([
            'date_time' => 'required|date',
            'duration'  => 'required|integer|min:1|max:10080',
            'cycle'     => 'required|string|in:once,daily,weekly,monthly,yearly',
        ]);

        $uid  = Auth::id();
        $from = Carbon::now();
        $to   = (clone $from)->addYear();

        $candidate = new Event([
            'name'        => $request->input('name', ''),
            'description' => $request->input('description'),
            'date_time'   => Carbon::parse($validated['date_time']),
            'duration'    => (int) $validated['duration'],
            'cycle'       => $validated['cycle'],
            'user_id'     => $uid,
        ]);

        $conflicts = $this->findConflicts($candidate, $uid, $from, $to, $request->input('exclude_id'));

        $normalized = $this->normalizeConflicts($conflicts);

        return response()->json([
            'conflict'  => !empty($normalized),
            'conflicts' => $normalized,
            'count'     => count($normalized),
            'window'    => [ 'from' => $from->toDateTimeString(), 'to' => $to->toDateTimeString() ],
        ]);
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

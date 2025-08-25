<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Group;
use App\Models\User;
use App\Models\Contact;
use App\Models\GroupMember;
use App\Models\GroupInvite;

class EventController extends Controller
{
    public function json(Request $request)
    {
        $uid = Auth::id();
        $ids = $this->accessibleEventIds($uid);
        $events = Event::whereIn('id', $ids)
            ->orderBy('date_time', 'asc')
            ->get();

        return response()->json($events);
    }

    public function index()
    {
        $uid = Auth::id();
        $ids = $this->accessibleEventIds($uid);
        $events = Event::whereIn('id', $ids)
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
                    'is_owner'    => (int)$ev->user_id === (int)Auth::id(),
                ];
            });

        return view('events.index', ['events' => $events]);
    }

    public function listJson(Request $request)
    {
        return $this->json($request);
    }

    private static function rangesOverlap(Carbon $aStart, Carbon $aEnd, Carbon $bStart, Carbon $bEnd): bool
    {
        return $aStart->lt($bEnd) && $aEnd->gt($bStart);
    }

    private function findConflicts(Event $candidate, int $userId, Carbon $from, Carbon $to, ?int $excludeId = null): array
    {
        $conflicts = [];

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

    private function accessibleEventIds(int $uid): array
    {
        // User's own events
        $ownIds = Event::where('user_id', $uid)->pluck('id')->toArray();

        // Events coming from groups where the user is a member
        $groupIds = GroupMember::where('user_id', $uid)->pluck('group_id');
        $groupEventIds = [];
        if ($groupIds->isNotEmpty()) {
            $groupEventIds = Group::whereIn('id', $groupIds)->pluck('event_id')->toArray();
        }

        // Merge & unique
        return array_values(array_unique(array_merge($ownIds, $groupEventIds)));
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

        try {
            // 1) Create event
            $event = Event::create([
                'name'        => $validated['name'],
                'description' => $validated['description'] ?? null,
                'date_time'   => $validated['date_time'],
                'duration'    => (int) $validated['duration'],
                'cycle'       => $validated['cycle'],
                'user_id'     => $uid,
            ]);

            // Create the group tied to this event, per your schema
            $group = Group::create([
                'admin_id'    => $uid,
                'event_id'    => $event->id,
                'tag'         => $event->name,
                'description' => $event->description,
            ]);

            // 3) Auto-join creator as member (if model/table exists)
            if (class_exists(GroupMember::class)) {
                $gm = new GroupMember();
                $gm->group_id = $group->id;
                $gm->user_id = $uid;
                $gm->save();
            }

            return response()->json($event, 201);
        } catch (\Throwable $e) {}
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

        $ids = $this->accessibleEventIds($uid);
        $events = Event::whereIn('id', $ids)
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

        $days = [];
        for ($i=0; $i<7; $i++) {
            $d = (clone $start)->addDays($i);
            $days[$d->toDateString()] = [ 'date' => $d->toDateString(), 'events' => [] ];
        }

        foreach ($events as $ev) {
            $base = Carbon::parse($ev->date_time);
            $baseStartDay = $base->copy()->startOfDay();
            $duration = max(1, (int)($ev->duration ?? 60)); 

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

        try {
            $group = Group::where('event_id', $event->id)->first();
            if ($group) {
                if (class_exists(GroupInvite::class)) {
                    GroupInvite::where('group_id', $group->id)->delete();
                }
                if (class_exists(GroupMember::class)) {
                    GroupMember::where('group_id', $group->id)->delete();
                }
                $group->delete();
            }
        } catch (\Throwable $e) {}

        $event->delete();

        return response()->json(null, 204);
    }
    public function eligibleInvitees(Event $event)
    {
        $group = Group::where('event_id', $event->id)->first();

        $contacts = Contact::where('user_id', Auth::id())->get();

        $invitedContactIds = GroupInvite::where('group_id', $group->id)->pluck('receiver_id')->toArray();
        $joinedContactIds = GroupMember::where('group_id', $group->id)->pluck('user_id')->toArray();

        return $contacts->map(function ($c) use ($invitedContactIds, $joinedContactIds) {
            $status = 'not';

            if (in_array($c->id, $joinedContactIds)) {
                $status = 'joined';
            } elseif (in_array($c->id, $invitedContactIds)) {
                $status = 'pending';
            }

            return [
                'id' => $c->id,
                'name' => $c->name,
                'status' => $status,
            ];
        });
    }

    public function invite(Event $event, Request $request)
    {
        $data = $request->validate([
            'contact_id' => 'required|exists:contacts,id',
        ]);

        $group = Group::where('event_id', $event->id)->first();

        $exists = GroupInvite::where('group_id', $group->id)
                    ->where('receiver_id', $data['contact_id'])
                    ->exists();

        if ($exists) {
            return response()->json(['message' => 'Already invited']);
        }

        $contact = Contact::where('id', $data['contact_id'])->first();

        GroupInvite::create([
            'group_id' => $group->id,
            'receiver_id' => User::where('phone_number', $contact->phone_number)->first()->id,
        ]);

        return response()->json(['success' => true]);
    }

    public function acceptInvite(Event $event, Request $request)
    {
        $userId = Auth::id();
        $group = Group::where('event_id', $event->id)->first();
        if (!$group) {
            abort(404);
        }
        $invite = GroupInvite::where('group_id', $group->id)
            ->where('receiver_id', $userId)
            ->first();
        if (!$invite) {
            abort(404);
        }
        $invite->delete();
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $userId,
        ]);
        return response()->json(['success' => true]);
    }

    public function rejectInvite(Event $event, Request $request)
    {
        $userId = Auth::id();
        $group = Group::where('event_id', $event->id)->first();
        if (!$group) {
            abort(404);
        }
        $invite = GroupInvite::where('group_id', $group->id)
            ->where('receiver_id', $userId)
            ->first();
        if (!$invite) {
            abort(404);
        }
        $invite->delete();
        return response()->json(['success' => true]);
    }
}
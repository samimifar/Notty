<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\User;
use App\Models\GroupInvite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GroupController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $groups = $user
            ? $user->groups()
                ->with(['event'])
                ->withCount('members')
                ->orderByDesc('groups.created_at')
                ->get()
            : collect();

        return view('groups.index', [
            'groups' => $groups,
        ]);
    }

    public function members(Group $group): JsonResponse
    {
        $auth = Auth::user();
        if (!$auth) {
            abort(401);
        }

        $isMember = $group->members()->where('users.id', $auth->id)->exists();
        if (!$isMember) {
            abort(403);
        }

        $list = $group->members()
            ->orderBy('users.name')
            ->get(['users.id', 'users.name'])
            ->map(function ($u) use ($group, $auth) {
                return [
                    'user_id'  => $u->id,
                    'name'     => $u->name,
                    'is_admin' => ((int)$group->admin_id === (int)$u->id),
                    'is_self'  => ((int)$auth->id === (int)$u->id),
                ];
            })
            ->values();

        return response()->json(['members' => $list]);
    }

    public function removeMember(Group $group, User $user): JsonResponse
    {
        $auth = Auth::user();
        if (!$auth) {
            abort(401);
        }

        if ((int)$group->admin_id !== (int)$auth->id) {
            abort(403);
        }

        if ((int)$user->id === (int)$group->admin_id) {
            return response()->json(['error' => 'Cannot remove the group admin.'], 422);
        }

        if (!$group->members()->where('users.id', $user->id)->exists()) {
            return response()->json(['ok' => true]);
        }

        $group->members()->detach($user->id);

        return response()->json(['ok' => true]);
    }

    public function leave(Group $group): JsonResponse
    {
        $auth = Auth::user();
        if (!$auth) {
            abort(401);
        }

        $isMember = $group->members()->where('users.id', $auth->id)->exists();
        if (!$isMember) {
            return response()->json(['ok' => true]);
        }

        if ((int)$group->admin_id === (int)$auth->id) {
            return response()->json([
                'error' => 'Group admin cannot leave the group.',
            ], 422);
        }

        $group->members()->detach($auth->id);

        return response()->json(['ok' => true]);
    }

    public function update(Request $request, Group $group): JsonResponse
    {
        $auth = Auth::user();
        if (!$auth) {
            abort(401);
        }

        if ((int)$group->admin_id !== (int)$auth->id) {
            abort(403);
        }

        $validated = $request->validate([
            'tag' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $group->tag = $validated['tag'];
        $group->description = $validated['description'] ?? null;
        $group->save();

        return response()->json([
            'id' => $group->id,
            'tag' => $group->tag,
            'description' => $group->description,
        ]);
    }

    public function invites(): JsonResponse
    {
        $auth = Auth::user();
        if (!$auth) {
            abort(401);
        }

        $invites = GroupInvite::where('receiver_id', $auth->id)
            ->with([
                'group:id,tag,description,event_id',
                'group.event:id,cycle,start_time,end_time,user_id',
                'group.admin:id,name'
            ])
            ->orderByDesc('id')
            ->get()
            ->map(function ($invite) {
                $g = $invite->group;
                $e = $g?->event;
                return [
                    'invite_id'  => $invite->id,
                    'group_id'   => $g?->id,
                    'tag'        => $g?->tag ?? '—',
                    'description'=> $g?->description,
                    'cycle'      => $e?->cycle,
                    'start_time' => $e?->start_time,
                    'end_time'   => $e?->end_time,
                ];
            });

        return response()->json(['invites' => $invites]);
    }

    public function respondInvite(Request $request, $inviteId): JsonResponse
    {
        $auth = Auth::user();
        if (!$auth) {
            abort(401);
        }

        $action = $request->input('action');
        if (!$action) {
            $path = '/'.ltrim($request->path(), '/');
            if (str_ends_with($path, '/accept')) {
                $action = 'accept';
            } elseif (str_ends_with($path, '/reject')) {
                $action = 'reject';
            }
        }
        if (!in_array($action, ['accept','reject'], true)) {
            return response()->json(['errors' => ['action' => ['Action must be accept or reject.']]], 422);
        }

        $invite = GroupInvite::find($inviteId);
        if (!$invite || (int)$invite->receiver_id !== (int)$auth->id) {
            abort(403);
        }

        if ($action === 'accept') {
            $group = $invite->group;
            $group->members()->syncWithoutDetaching([
                $auth->id => [
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        $invite->delete();

        return response()->json(['ok' => true]);
    }
}

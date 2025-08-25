<x-app-layout>
    @php

        $ordinal = function($n) {
            $n = (int) $n;
            $suffix = 'th';
            if (!in_array(($n % 100), [11,12,13])) {
                switch ($n % 10) {
                    case 1: $suffix = 'st'; break;
                    case 2: $suffix = 'nd'; break;
                    case 3: $suffix = 'rd'; break;
                }
            }
            return $n . $suffix;
        };

        $formatEventTime = function($event) use ($ordinal) {
            if (!$event) return '—';
            $cycle = $event->cycle ?? null;

            // Resolve start/end
            $startsAtRaw = $event->starts_at ?? $event->date_time ?? null;
            try {
                $start = $startsAtRaw ? \Carbon\Carbon::parse($startsAtRaw) : null;
            } catch (Exception $e) {
                $start = null;
            }
            $duration = (int) ($event->duration ?? $event->duration_minutes ?? 0);
            $end   = ($start && $duration > 0) ? $start->copy()->addMinutes($duration) : null;

            $timeRange = ($start && $end)
                ? $start->format('H:i') . '-' . $end->format('H:i')
                : ($start ? $start->format('H:i') : '');

            // Fallback: if starts_at is absent but event keeps plain start_time/end_time (HH:MM)
            if (!$timeRange) {
                $startTimeStr = $event->start_time ?? null; // e.g. '13:00'
                $endTimeStr   = $event->end_time   ?? null; // e.g. '15:30'
                if ($startTimeStr || $endTimeStr) {
                    $timeRange = trim(($startTimeStr ?: '') . ($endTimeStr ? ('-' . $endTimeStr) : ''));
                }
            }

            switch ($cycle) {
                case 'once':
                    if ($start) {
                        return $start->format('d/m/Y') . ' - ' . $timeRange;
                    }
                    return $timeRange ?: '—';

                case 'daily':
                    return $timeRange ?: '—';

                case 'weekly':
                    // Prefer explicit weekday field if exists; else from start
                    $weekday = $event->weekday ?? null; // 0-6 or 1-7 depending on schema
                    if ($weekday !== null) {
                        // normalize to Carbon: 0 (Sun) .. 6 (Sat)
                        $w = (int) $weekday;
                        // If app uses 1=Mon..7=Sun, convert
                        if ($w >= 1 && $w <= 7) { $w = $w % 7; }
                        $dayName = \Carbon\Carbon::create()->startOfWeek(\Carbon\Carbon::SUNDAY)->addDays($w)->format('l');
                    } elseif ($start) {
                        $dayName = $start->format('l');
                    } else {
                        $dayName = '—';
                    }
                    return trim($dayName . ' - ' . $timeRange, ' -');

                case 'monthly':
                    $day = $event->month_day ?? ($start ? $start->day : null);
                    $dayLabel = $day ? $ordinal($day) : '—';
                    return trim($dayLabel . ' - ' . $timeRange, ' -');

                case 'yearly':
                    if ($start) {
                        return $start->format('d M') . ' - ' . $timeRange;
                    }
                    // Fallback: if month/day exist on event
                    $m = $event->month ?? null; $d = $event->day ?? null;
                    if ($m && $d) {
                        $tmp = \Carbon\Carbon::create(null, (int)$m, (int)$d, 0, 0, 0);
                        return $tmp->format('d M') . ($timeRange ? (' - ' . $timeRange) : '');
                    }
                    return $timeRange ?: '—';
            }

            // Unknown cycle
            return $timeRange ?: '—';
        };

        $groupsPayload = $groups->map(function($g) use ($formatEventTime) {
            return [
                'id' => $g->id,
                'tag' => $g->tag,
                'description' => $g->description,
                'cycle' => optional($g->event)->cycle,
                'time_label' => $formatEventTime(optional($g->event)),
                'members_count' => $g->members_count,
                'is_admin' => ($g->admin_id === auth()->id()),
            ];
        })->values();
    @endphp

    <div class="py-6" x-data="groupsPage()"
    @group-members.window="openMembers($event.detail.id)"
    @group-manage.window="openManage($event.detail.id)"
    @group-leave.window="askLeave($event.detail.id)"
    >
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-lg p-4 h-[560px] flex flex-col">
                <!-- Header -->
                <div class="flex items-center justify-between mb-3">
                  <h3 class="text-lg font-semibold">Your Groups</h3>
                  <template x-if="invitesCount > 0">
                    <button type="button" @click="loadInvites(true)" class="relative inline-flex items-center gap-2 px-3 py-1.5 rounded border text-sm hover:bg-gray-50">
                      <span>Incoming Invites</span>
                      <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-red-600 text-white text-xs" x-text="invitesCount"></span>
                    </button>
                  </template>
                </div>
                <!-- Invites Modal -->
                <div x-cloak x-show="invitesOpen" class="fixed inset-0 z-[75] flex items-center justify-center" @keydown.escape.window="invitesOpen=false">
                  <div class="absolute inset-0 bg-black/40"></div>
                  <div class="relative bg-white rounded-lg shadow-lg w-full max-w-lg p-5">
                    <div class="flex items-center justify-between mb-3">
                      <h4 class="text-base font-semibold">Incoming Invites</h4>
                      <button type="button" class="text-sm px-2 py-1 border rounded" @click="invitesOpen=false">Cancel</button>
                    </div>
                    <template x-if="invitesLoading">
                      <div class="py-8 text-center text-gray-500 text-sm">Loading…</div>
                    </template>
                    <template x-if="invitesError">
                      <div class="py-2 text-red-600 text-sm" x-text="invitesError"></div>
                    </template>
                    <template x-if="!invitesLoading && !invitesError && invitesCount===0">
                      <div class="py-8 text-center text-gray-500 text-sm">No invites right now.</div>
                    </template>

                    <template x-if="!invitesLoading && !invitesError && invitesCount>0">
                      <ul class="divide-y divide-gray-100 max-h-80 overflow-y-auto">
                        <template x-for="inv in invites" :key="inv.invite_id">
                          <li class="flex items-center justify-between gap-3 py-3">
                            <div class="flex items-center gap-3">
                              <div
                                class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 text-sm font-semibold"
                                x-text="(inv.tag ? inv.tag[0] : '?').toUpperCase()"
                              ></div>
                              <div>
                                <div class="text-sm font-medium text-gray-900" x-text="inv.tag || '—'"></div>
                                <div class="text-xs text-gray-500" x-text="inv.description || 'No description'"></div>
                              </div>
                              <!-- Inline conflicts block -->
                              <template x-if="inv.has_conflict && inv.conflicts && inv.conflicts.length">
                                <div class="mt-1 text-xs text-red-700">
                                  <div class="font-medium">Conflicts:</div>
                                  <ul class="list-disc list-inside space-y-0.5">
                                    <template x-for="c in inv.conflicts" :key="c">
                                      <li x-text="c"></li>
                                    </template>
                                  </ul>
                                  <div class="mt-1 text-[11px] text-red-600">You can still accept and proceed anyway.</div>
                                </div>
                              </template>
                            </div>
                            <div class="flex items-center gap-2">
                              <template x-if="!inv.requireForce">
                                <button type="button" class="inline-flex items-center justify-center w-8 h-8 rounded-full border border-green-300 text-green-700 hover:bg-green-50" @click="acceptInvite(inv.invite_id)">
                                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-7.25 7.25a1 1 0 01-1.414 0l-3-3a1 1 0 111.414-1.414l2.293 2.293 6.543-6.543a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                </button>
                              </template>
                              <template x-if="inv.requireForce">
                                <button type="button" class="inline-flex items-center justify-center px-2.5 h-8 rounded border border-red-300 text-red-700 hover:bg-red-50 text-xs" @click="acceptInvite(inv.invite_id, true)">
                                  Proceed
                                </button>
                              </template>
                              <button type="button" class="inline-flex items-center justify-center w-8 h-8 rounded-full border border-red-300 text-red-700 hover:bg-red-50" @click="rejectInvite(inv.invite_id)">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                              </button>
                            </div>
                          </li>
                        </template>
                      </ul>
                    </template>
                  </div>
                </div>
                <div class="h-px bg-gray-200 mb-3"></div>

                <template x-if="!items || items.length === 0">
                    <div class="flex-1 flex items-center justify-center text-gray-500 text-sm">
                        You are not a member of any groups yet.
                    </div>
                </template>

                <template x-if="items && items.length">
                    <div class="flex-1 overflow-y-auto">
                        <table class="min-w-full text-sm border border-gray-200">
                            <thead class="bg-gray-50 text-gray-700">
                                <tr>
                                    <th class="px-4 py-2 text-left  w-[32%] border-b border-gray-200">Group</th>
                                    <th class="px-4 py-2 text-center w-[17%] border-b border-gray-200">Cycle</th>
                                    <th class="px-4 py-2 text-center w-[27%] border-b border-gray-200">Time</th>
                                    <th class="px-4 py-2 text-center w-[8%]  border-b border-gray-200">Members</th>
                                    <th class="px-4 py-2 text-center w-[16%] border-b border-gray-200">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100" x-ref="tbody">
                                <template x-for="g in items" :key="g.id">
                                    <tr>
                                        <!-- Group with Bootstrap tooltip -->
                                        <td class="px-4 py-2 align-top text-left">
                                            <span class="font-medium text-gray-900" x-text="g.tag || '—'"></span>
                                        </td>
                                        <!-- Cycle -->
                                        <td class="px-4 py-2 align-top text-center text-gray-900" x-text="g.cycle ? g.cycle : '—'"></td>
                                        <!-- Time label -->
                                        <td class="px-4 py-2 align-top text-center text-gray-900" x-text="g.time_label"></td>
                                        <!-- Members count -->
                                        <td class="px-4 py-2 align-top text-center text-gray-900" x-text="g.members_count ?? 0"></td>
                                        <!-- Actions -->
                                        <td class="px-4 py-2 align-top text-center whitespace-nowrap">
                                            <template x-if="g.is_admin">
                                                <div class="inline-flex items-center justify-center space-x-2">
                                                    <button type="button" class="px-3 py-1 rounded border text-xs hover:bg-gray-50" @click="$dispatch('group-manage', { id: g.id })">Manage</button>
                                                    <button type="button" class="px-3 py-1 rounded border text-xs hover:bg-gray-50" @click="$dispatch('group-members', { id: g.id })">Members</button>
                                                </div>
                                            </template>
                                            <template x-if="!g.is_admin">
                                                <div class="inline-flex items-center justify-center">
                                                    <button type="button" class="px-3 py-1 rounded border text-xs hover:bg-gray-50 text-red-600 border-red-200" @click="$dispatch('group-leave', { id: g.id })">Leave</button>
                                                </div>
                                            </template>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </template>

                <!-- Members Modal -->
                <div x-cloak x-show="membersOpen" class="fixed inset-0 z-50 flex items-center justify-center" @keydown.escape.window="membersOpen=false">
                  <div class="absolute inset-0 bg-black/40"></div>
                  <div class="relative bg-white rounded-lg shadow-lg w-full max-w-lg p-5">
                    <div class="flex items-center justify-between mb-3">
                      <h4 class="text-base font-semibold">Group Members</h4>
                      <button type="button" class="text-sm px-2 py-1 border rounded" @click="membersOpen=false">Close</button>
                    </div>
                    <template x-if="membersLoading">
                      <div class="py-8 text-center text-gray-500 text-sm">Loading…</div>
                    </template>
                    <template x-if="membersError">
                      <div class="py-2 text-red-600 text-sm" x-text="membersError"></div>
                    </template>
                    <template x-if="!membersLoading && !membersError && (!members || members.length===0)">
                      <div class="py-8 text-center text-gray-500 text-sm">No members in this group yet.</div>
                    </template>
                    <template x-if="!membersLoading && !membersError && members && members.length">
                      <ul class="divide-y divide-gray-100 max-h-80 overflow-y-auto">
                        <template x-for="m in members" :key="m.user_id">
                          <li class="flex items-center justify-between gap-3 py-3">
                            <div class="flex items-center gap-3">
                              <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 text-sm font-semibold" x-text="(m.name||'')[0]?.toUpperCase() || '?' "></div>
                              <div>
                                <div class="text-sm font-medium text-gray-900" x-text="m.name || '—'"></div>
                                <div class="text-xs text-gray-500">
                                  <template x-if="m.is_admin"><span class="px-1.5 py-0.5 rounded bg-yellow-100 text-yellow-800 text-[11px]">Admin</span></template>
                                </div>
                              </div>
                            </div>
                            <div>
                              <template x-if="m.is_admin">
                                <span class="px-1.5 py-0.5 rounded bg-yellow-100 text-yellow-800 text-[11px]">Admin</span>
                              </template>
                              <template x-if="!m.is_admin && !m.is_self">
                                <button type="button" class="px-3 py-1 rounded border text-xs text-red-600 border-red-200 hover:bg-red-50" @click="askRemove(m)">Remove</button>
                              </template>
                              <template x-if="!m.is_admin && m.is_self">
                                <button type="button" class="px-3 py-1 rounded border text-xs text-gray-400 border-gray-200 cursor-not-allowed" disabled>Remove</button>
                              </template>
                            </div>
                          </li>
                        </template>
                      </ul>
                    </template>
                  </div>
                </div>

                <!-- Confirm Remove Modal -->
                <div x-cloak x-show="removeOpen" class="fixed inset-0 z-[60] flex items-center justify-center" @keydown.escape.window="removeOpen=false">
                  <div class="absolute inset-0 bg-black/40"></div>
                  <div class="relative bg-white rounded-lg shadow-lg w-full max-w-sm p-5">
                    <h4 class="text-base font-semibold mb-2">Remove member</h4>
                    <p class="text-sm text-gray-600 mb-4">Are you sure you want to remove <span class="font-medium" x-text="removeTarget?.name"></span> from this group?</p>
                    <div class="flex items-center justify-end gap-2">
                      <button type="button" class="text-sm px-3 py-1 border rounded" @click="removeOpen=false">Cancel</button>
                      <button type="button" class="text-sm px-3 py-1 border rounded bg-red-600 text-white" :disabled="removing" @click="confirmRemove()" x-text="removing ? 'Removing…' : 'Remove'"></button>
                    </div>
                  </div>
                </div>

                <!-- Confirm Leave Modal -->
                <div x-cloak x-show="leaveOpen" class="fixed inset-0 z-[60] flex items-center justify-center" @keydown.escape.window="leaveOpen=false">
                  <div class="absolute inset-0 bg-black/40"></div>
                  <div class="relative bg-white rounded-lg shadow-lg w-full max-w-sm p-5">
                    <h4 class="text-base font-semibold mb-2">Leave group</h4>
                    <p class="text-sm text-gray-600 mb-4">Are you sure you want to leave this group?</p>
                    <div class="flex items-center justify-end gap-2">
                      <button type="button" class="text-sm px-3 py-1 border rounded" @click="leaveOpen=false">Cancel</button>
                      <button type="button" class="text-sm px-3 py-1 border rounded bg-red-600 text-white" :disabled="leaving" @click="confirmLeave()" x-text="leaving ? 'Leaving…' : 'Leave'"></button>
                    </div>
                  </div>
                </div>

                <!-- Manage Group Modal -->
                <div x-cloak x-show="manageOpen" class="fixed inset-0 z-[70] flex items-center justify-center" @keydown.escape.window="manageOpen=false">
                  <div class="absolute inset-0 bg-black/40"></div>
                  <div class="relative bg-white rounded-lg shadow-lg w-full max-w-md p-5">
                    <div class="flex items-center justify-between mb-3">
                      <h4 class="text-base font-semibold">Manage Group</h4>
                    </div>
                    <form @submit.prevent="saveManage()" class="space-y-3">
                      <div>
                        <label class="block text-xs font-medium mb-1">Group Name</label>
                        <input type="text" class="w-full p-2 border rounded text-sm" x-model="manageForm.tag" required>
                      </div>
                      <div>
                        <label class="block text-xs font-medium mb-1">Description</label>
                        <textarea class="w-full p-2 border rounded text-sm" rows="3" x-model="manageForm.description"></textarea>
                      </div>
                      <template x-if="manageError">
                        <div class="text-red-600 text-sm" x-text="manageError"></div>
                      </template>
                      <div class="flex items-center justify-end gap-2">
                        <button type="button" class="text-sm px-3 py-1 border rounded" @click="manageOpen=false">Cancel</button>
                        <button type="submit" class="text-sm px-3 py-1 border rounded bg-indigo-600 text-white" :disabled="manageSaving" x-text="manageSaving ? 'Saving…' : 'Save'"></button>
                      </div>
                    </form>
                  </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
<script>
  window.groupsPage = function () {
    return {
      items: @js($groupsPayload),
      debugInvitesRaw: '',

      init(){
          this.loadInvites(false);
      },

      // Members modal state
      membersOpen: false,
      membersLoading: false,
      membersError: null,
      membersGroupId: null,
      members: [],

      // Remove confirm modal state
      removeOpen: false,
      removing: false,
      removeTarget: null, // {user_id, name}

      // Leave confirm modal state
      leaveOpen: false,
      leaving: false,
      leaveGroupId: null,

      async openMembers(groupId){
          this.membersGroupId = groupId;
          this.membersOpen = true;
          this.membersLoading = true;
          this.membersError = null;
          this.members = [];
          try{
              const url = `/groups/${groupId}/members`;
              const res = await fetch(url, { headers: { 'Accept': 'application/json' }});
              if(!res.ok){ throw new Error('Failed to load members'); }
              const data = await res.json();
              this.members = Array.isArray(data) ? data : (data.members || []);
          }catch(err){
              this.membersError = err?.message || 'Error loading members';
          }finally{
              this.membersLoading = false;
          }
      },
      askRemove(member){
          this.removeTarget = member;
          this.removeOpen = true;
      },
      async confirmRemove(){
          if(!this.removeTarget || !this.membersGroupId) return;
          this.removing = true;
          try{
              const url = `/groups/${this.membersGroupId}/members/${this.removeTarget.user_id}`;
              const res = await fetch(url, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': `{{ csrf_token() }}`, 'Accept': 'application/json' }});
              if(!res.ok){ throw new Error('Failed to remove member'); }
              this.members = this.members.filter(m => m.user_id !== this.removeTarget.user_id);
              this.removeOpen = false;
              this.removeTarget = null;
          }catch(err){
              alert(err?.message || 'Remove failed');
          }finally{
              this.removing = false;
          }
      },

      askLeave(groupId){
          this.leaveGroupId = groupId;
          this.leaveOpen = true;
      },
      async confirmLeave(){
          if(!this.leaveGroupId) return;
          this.leaving = true;
          try{
              const url = `/groups/${this.leaveGroupId}/leave`;
              const res = await fetch(url, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': `{{ csrf_token() }}`, 'Accept': 'application/json' }});
              if(!res.ok){ throw new Error('Failed to leave group'); }
              // Optimistically remove from list or reload
              this.items = (this.items || []).filter(g => g.id !== this.leaveGroupId);
              await this.reloadGroups();
              this.leaveOpen = false;
              this.leaveGroupId = null;
          }catch(err){
              alert(err?.message || 'Leave failed');
          }finally{
              this.leaving = false;
          }
      },

      // Manage modal state
      manageOpen: false,
      manageSaving: false,
      manageError: null,
      manageForm: { id: null, tag: '', description: '' },

      openManage(id){
          const g = this.items.find(it => it.id === id);
          if(!g) return;
          this.manageForm = { id: g.id, tag: g.tag || '', description: g.description || '' };
          this.manageError = null;
          this.manageOpen = true;
      },
      async saveManage(){
          if(!this.manageForm.id) return;
          this.manageSaving = true;
          this.manageError = null;
          try{
              const url = `/groups/${this.manageForm.id}`;
              const res = await fetch(url, {
                  method: 'PATCH',
                  headers: {
                      'Content-Type': 'application/json',
                      'X-CSRF-TOKEN': `{{ csrf_token() }}`,
                      'Accept': 'application/json'
                  },
                  body: JSON.stringify({ tag: this.manageForm.tag, description: this.manageForm.description })
              });
              if(!res.ok){
                  let msg = 'Update failed';
                  try { const j = await res.json(); msg = (j.message || j.error || JSON.stringify(j)); } catch(e) {}
                  throw new Error(msg);
              }
              const updated = await res.json();
              const idx = this.items.findIndex(it => it.id === this.manageForm.id);
              if(idx !== -1){
                  this.items[idx].tag = updated.tag ?? this.manageForm.tag;
                  this.items[idx].description = updated.description ?? this.manageForm.description;
              }
              await this.reloadGroups();
              this.manageOpen = false;
          }catch(err){
              this.manageError = err?.message || 'Could not update';
          }finally{
              this.manageSaving = false;
          }
      },

      // reload list after updates
      async reloadGroups(){
          try{
              const res = await fetch('/groups', { headers: { 'Accept': 'application/json' }});
              if(!res.ok) return;
              const data = await res.json();
              const list = Array.isArray(data) ? data : (data.groups || data.items || []);
              if(Array.isArray(list)) this.items = list;
          }catch(e){ /* ignore */ }
      },

      // Invites state
      invitesOpen: false,
      invitesLoading: false,
      invitesError: null,
      invites: [],
      invitesCount: 0,
      async loadInvites(open=false){
        this.invitesLoading = true; this.invitesError = null; this.debugInvitesRaw = '';
        try{
          const res = await fetch('/groups/invites', { headers: { 'Accept': 'application/json' } });
          const text = await res.text();
          let data;
          try { data = JSON.parse(text); }
          catch(e){
            this.invitesError = 'Invites API did not return JSON. See console/debug.';
            return;
          }
          const list = Array.isArray(data) ? data : (data.invites || data.items || []);
          const normalized = (list || []).map(i => {
            const obj = {
              invite_id: i.invite_id ?? i.id ?? i.inviteID,
              group_id:  i.group_id  ?? i.groupID  ?? null,
              tag:        i.tag       ?? (i.group?.tag ?? null),
              sender:      i.sender ?? (i.admin?.name ?? null),
              description:i.description ?? (i.group?.description ?? null),
              cycle:      i.cycle     ?? i.event?.cycle ?? null,
              start_time: i.start_time?? i.event?.start_time ?? null,
              end_time:   i.end_time  ?? i.event?.end_time   ?? null,
            };
            return obj;
          });
          this.invites = normalized;
          this.invitesCount = normalized.length || 0;
          if(open && this.invitesCount>0){ this.invitesOpen = true; }
        }catch(e){
          this.invitesError = e?.message || 'Error loading invites';
        }finally{
          this.invitesLoading = false;
        }
      },
      async acceptInvite(id, force=false){
        this.invitesError = null;
        const idx = this.invites.findIndex(i => i.invite_id === id);
        try{
          if(force === true){
            const res = await fetch(`/groups/invites/${id}/accept?force=true`, {
              method:'POST', headers:{ 'X-CSRF-TOKEN': `{{ csrf_token() }}`, 'Accept':'application/json' }
            });
            if(!res.ok) throw new Error('Accept failed');
            this.invites = this.invites.filter(i => i.invite_id !== id);
            this.invitesCount = Math.max(0, this.invitesCount - 1);
            await this.reloadGroups();
            if(this.invitesCount===0) this.invitesOpen = false;
            return;
          }

          const res = await fetch(`/groups/invites/${id}/accept?check=1`, {
            method:'POST', headers:{ 'X-CSRF-TOKEN': `{{ csrf_token() }}`, 'Accept':'application/json' }
          });

          if(res.status === 409 || res.status === 422){
            let payload = {};
            try { payload = await res.json(); } catch(e) {}
            const conflicts = Array.isArray(payload.conflicts) ? payload.conflicts : [];
            if(idx >= 0){
              this.invites[idx].has_conflict = conflicts.length > 0;
              this.invites[idx].conflicts = conflicts;
              this.invites[idx].requireForce = true;
            }
            return; // UI now shows inline conflicts and a Proceed button
          }

          if(res.ok){
            this.invites = this.invites.filter(i => i.invite_id !== id);
            this.invitesCount = Math.max(0, this.invitesCount - 1);
            await this.reloadGroups();
            if(this.invitesCount===0) this.invitesOpen = false;
            return;
          }

          let msg = 'Accept failed';
          try { const j = await res.json(); msg = j.message || JSON.stringify(j); } catch(e) {}
          throw new Error(msg);

        }catch(e){
          this.invitesError = e?.message || 'Accept failed';
        }
      },
      async rejectInvite(id){
        try{
          const res = await fetch(`/groups/invites/${id}/reject`, { method:'POST', headers:{ 'X-CSRF-TOKEN': `{{ csrf_token() }}`, 'Accept':'application/json' } });
          if(!res.ok) throw new Error('Reject failed');
          this.invites = this.invites.filter(i => i.invite_id !== id);
          this.invitesCount = Math.max(0, this.invitesCount - 1);
          if(this.invitesCount===0) this.invitesOpen = false;
        }catch(e){
          this.invitesError = e?.message || 'Reject failed';
        }
      }
    }
  }
</script>
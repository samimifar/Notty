<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-lg p-4 h-[560px] flex flex-col">
                <div x-data="eventsList()" x-init="init()" class="flex-1 flex flex-col text-gray-900">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-lg font-semibold">Your Events</h3>
                    </div>
                    <div class="h-px bg-gray-200 mb-2"></div>

                    <div class="flex-1 overflow-y-auto">
                        <div class="overflow-x-auto">
                            <table id="main-events-table" class="min-w-full border border-gray-200 divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left font-medium text-gray-600">Title</th>
                                        <th class="px-4 py-2 text-left font-medium text-gray-600">Description</th>
                                        <th class="px-4 py-2 text-left font-medium text-gray-600">Cycle</th>
                                        <th class="px-4 py-2 text-left font-medium text-gray-600">Time</th>
                                        <th class="px-4 py-2 text-center font-medium text-gray-600">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse($events as $event)
                                    @php
                                    // Normalize array/object access
                                    $get = function($key, $default = null) use ($event) {
                                    if (is_array($event)) return data_get($event, $key, $default);
                                    return data_get($event, $key, $default);
                                    };

                                    $evId = $get('id');
                                    $title = $get('title', $get('name', '—'));
                                    $desc = $get('description', '—');
                                    $cycle = ucfirst($get('cycle', 'once'));
                                    $start = $get('start', $get('date_time'));
                                    // Try to build an end from date_time + duration when end is missing
                                    $end = $get('end');
                                    if (!$end && $start && ($dur = $get('duration'))) {
                                    try { $end = \Carbon\Carbon::parse($start)->copy()->addMinutes((int) $dur)->format('Y-m-d H:i'); } catch (\Throwable $e) { /* ignore */ }
                                    }
                                    // Insert time range string computation
                                    $time = '—';
                                    try {
                                    if ($start) {
                                    $s = \Carbon\Carbon::parse($start);
                                    $e = $end ? \Carbon\Carbon::parse($end)
                                    : (($dur = $get('duration')) ? $s->copy()->addMinutes((int)$dur) : null);
                                    if ($e) {
                                    $time = $s->format('H:i') . ' – ' . $e->format('H:i');
                                    }
                                    }
                                    } catch (\Throwable $e) { /* ignore formatting errors */ }
                                    // Compute UTC ISO strings for timezone-safe JS
                                    $startIso = null; $endIso = null;
                                    try {
                                    if ($start) {
                                    $sObj = \Carbon\Carbon::parse($start)->utc();
                                    $startIso = $sObj->toIso8601String();
                                    if ($end) {
                                    $eObj = \Carbon\Carbon::parse($end)->utc();
                                    $endIso = $eObj->toIso8601String();
                                    } elseif ($dur = $get('duration')) {
                                    $eObj = $sObj->copy()->addMinutes((int)$dur);
                                    $endIso = $eObj->toIso8601String();
                                    }
                                    }
                                    } catch (\Throwable $e) { /* ignore */ }
                                    // Determine if current user is the owner/admin of this event (robust across shapes)
                                    $currentUserId = (int) auth()->id();
                                    // Try several common fields (flat and nested) that might carry the owner's id
                                    $ownerId = $get('user_id')
                                        ?? $get('owner_id')
                                        ?? $get('creator_id')
                                        ?? $get('created_by')
                                        ?? $get('admin_id')
                                        ?? $get('group.admin_id')
                                        ?? $get('group_owner_id');

                                    $ownerId = is_numeric($ownerId) ? (int) $ownerId : null;

                                    // Some payloads send explicit booleans
                                    $explicitOwnerFlags = (bool) $get('is_owner') || (bool) $get('owner') || (bool) $get('is_admin') || (bool) $get('owned');

                                    $isOwner = $explicitOwnerFlags || ($ownerId !== null && $ownerId === $currentUserId);
                                    @endphp
                                    <tr id="ev-row-{{ (int) $evId }}"
                                        data-owner="{{ $isOwner ? 1 : 0 }}"
                                        data-owner-id="{{ $ownerId !== null ? (int) $ownerId : '' }}"
                                        data-user-id="{{ (int) auth()->id() }}">
                                        <td class="px-4 py-2"><span class="js-title">{{ $title }}</span></td>
                                        <td class="px-4 py-2"><span class="js-desc">{{ $desc }}</span></td>
                                        <td class="px-4 py-2"><span class="js-cycle">{{ $cycle }}</span></td>
                                        <td class="px-4 py-2">
                                            <span class="js-time">{{ $time }}</span>
                                        </td>
                                        <td class="px-4 py-2 text-center">
                                            @if($evId && $isOwner === true)
                                            <div class="inline-flex gap-2">
                                                <button type="button" @click="openEdit({{ (int) $evId }})" class="px-3 py-1 bg-indigo-600 text-white rounded hover:bg-indigo-700 text-xs">Edit</button>
                                                <button type="button" @click="askDelete({ id: {{ (int) $evId }}, title: @js($title) })" class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 text-xs">Delete</button>
                                                <button type="button" @click="openInvite({{ (int) $evId }})" class="px-3 py-1 bg-gray-600 text-white rounded hover:bg-gray-700 text-xs">
                                                    Invite
                                                </button>
                                            </div>
                                            @else
                                            <span class="text-gray-400 text-xs">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">No events to show.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Invite Contacts Modal (Tailwind/Alpine) -->
                        <div x-cloak x-show="showInvite" class="fixed inset-0 z-50 flex items-center justify-center">
                            <div class="absolute inset-0 bg-black/40" @click="closeInvite()"></div>
                            <div class="relative bg-white rounded-lg shadow-lg w-full max-w-lg p-5">
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="text-base font-semibold">Invite Contacts</h3>
                                    <button class="text-gray-500" @click="closeInvite()">✕</button>
                                </div>

                                <div class="space-y-2 max-h-80 overflow-y-auto" id="inviteList">
                                    <template x-if="!invitees.length">
                                        <div class="text-sm text-gray-500">No contacts found.</div>
                                    </template>
                                    <template x-for="c in invitees" :key="'inv-'+c.id">
                                        <div class="flex items-center justify-between gap-3 p-2 border rounded">
                                            <div class="flex items-center gap-3">
                                                <!-- Avatar with first letter -->
                                                <div class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-semibold" x-text="(c.name || '?').charAt(0).toUpperCase()"></div>
                                                <div class="text-sm" x-text="c.name"></div>
                                            </div>
                                            <div>
                                                <!-- One unified button; style depends on status -->
                                                <button
                                                    class="px-3 py-1 rounded text-xs"
                                                    :class="{
                                                        'bg-indigo-600 text-white hover:bg-indigo-700': c.status === 'not',
                                                        'bg-gray-400 text-white cursor-not-allowed': c.status === 'pending',
                                                        'bg-green-600 text-white cursor-not-allowed': c.status === 'joined'
                                                    }"
                                                    :disabled="c.status !== 'not'"
                                                    @click="sendInvite(c)"
                                                    x-text="c.status === 'joined' ? 'Joined' : (c.status === 'pending' ? 'Pending' : 'Invite')"
                                                ></button>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <div class="flex justify-end gap-2 mt-4">
                                    <button class="px-3 py-1 border rounded text-sm" @click="closeInvite()">Close</button>
                                </div>
                            </div>
                        </div>

                        <!-- Edit Event Modal -->
                        <div x-cloak x-show="showEdit" class="fixed inset-0 z-50 flex items-center justify-center">
                            <div class="absolute inset-0 bg-black/40" @click="showEdit=false"></div>
                            <div class="relative bg-white rounded-lg shadow-lg w-full max-w-lg p-5">
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="text-base font-semibold">Edit event</h3>
                                    <button class="text-gray-500" @click="showEdit=false" type="button">✕</button>
                                </div>

                                <!-- Wrap fields in a real form so HTML5 validation works -->
                                <form @submit.prevent="save()" class="space-y-3" novalidate>
                                    <div>
                                        <label class="block text-xs text-gray-600 mb-1">Title</label>
                                        <input type="text"
                                               class="w-full border rounded p-2 text-sm"
                                               x-model="form.name"
                                               name="name"
                                               required>
                                        <template x-if="errors.name">
                                            <div class="text-xs text-red-600" x-text="errors.name[0]"></div>
                                        </template>
                                    </div>

                                    <div>
                                        <label class="block text-xs text-gray-600 mb-1">Description</label>
                                        <textarea class="w-full border rounded p-2 text-sm"
                                                  rows="3"
                                                  x-model="form.description"
                                                  name="description"></textarea>
                                        <template x-if="errors.description">
                                            <div class="text-xs text-red-600" x-text="errors.description[0]"></div>
                                        </template>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs text-gray-600 mb-1">Duration (min)</label>
                                            <input type="number"
                                                   min="1"
                                                   class="w-full border rounded p-2 text-sm"
                                                   x-model.number="form.duration"
                                                   name="duration"
                                                   required>
                                            <template x-if="errors.duration">
                                                <div class="text-xs text-red-600" x-text="errors.duration[0]"></div>
                                            </template>
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-600 mb-1">Cycle</label>
                                            <select class="w-full border rounded p-2 text-sm"
                                                    x-model="form.cycle"
                                                    name="cycle"
                                                    required>
                                                <option value="once">once</option>
                                                <option value="daily">daily</option>
                                                <option value="weekly">weekly</option>
                                                <option value="monthly">monthly</option>
                                                <option value="yearly">yearly</option>
                                            </select>
                                            <template x-if="errors.cycle">
                                                <div class="text-xs text-red-600" x-text="errors.cycle[0]"></div>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- Dynamic Cycle Fields -->
                                    <template x-if="form.cycle === 'once'">
                                        <div>
                                            <label class="block text-xs text-gray-600 mb-1">Start (date &amp; time)</label>
                                            <input type="datetime-local"
                                                   class="w-full border rounded p-2 text-sm"
                                                   x-model="form.date_time"
                                                   name="date_time"
                                                   :required="form.cycle==='once'">
                                            <template x-if="errors.date_time">
                                                <div class="text-xs text-red-600" x-text="errors.date_time[0]"></div>
                                            </template>
                                        </div>
                                    </template>

                                    <template x-if="form.cycle === 'daily'">
                                        <div>
                                            <label class="block text-xs text-gray-600 mb-1">Time</label>
                                            <input type="time"
                                                   class="w-full border rounded p-2 text-sm"
                                                   x-model="timeStr"
                                                   name="time_daily"
                                                   :required="form.cycle==='daily'">
                                        </div>
                                    </template>

                                    <template x-if="form.cycle === 'weekly'">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-xs text-gray-600 mb-1">Weekday</label>
                                                <select class="w-full border rounded p-2 text-sm"
                                                        x-model="weekday"
                                                        name="weekday"
                                                        :required="form.cycle==='weekly'">
                                                    <option value="6">Saturday</option>
                                                    <option value="0">Sunday</option>
                                                    <option value="1">Monday</option>
                                                    <option value="2">Tuesday</option>
                                                    <option value="3">Wednesday</option>
                                                    <option value="4">Thursday</option>
                                                    <option value="5">Friday</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs text-gray-600 mb-1">Time</label>
                                                <input type="time"
                                                       class="w-full border rounded p-2 text-sm"
                                                       x-model="timeStr"
                                                       name="time_weekly"
                                                       :required="form.cycle==='weekly'">
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="form.cycle === 'monthly'">
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                            <div>
                                                <label class="block text-xs text-gray-600 mb-1">Month</label>
                                                <select class="w-full border rounded p-2 text-sm"
                                                        x-model="month"
                                                        name="month_monthly"
                                                        :required="form.cycle==='monthly'">
                                                    <option value="1">January</option>
                                                    <option value="2">February</option>
                                                    <option value="3">March</option>
                                                    <option value="4">April</option>
                                                    <option value="5">May</option>
                                                    <option value="6">June</option>
                                                    <option value="7">July</option>
                                                    <option value="8">August</option>
                                                    <option value="9">September</option>
                                                    <option value="10">October</option>
                                                    <option value="11">November</option>
                                                    <option value="12">December</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs text-gray-600 mb-1">Day</label>
                                                <select class="w-full border rounded p-2 text-sm"
                                                        x-model="dom"
                                                        name="day_monthly"
                                                        :required="form.cycle==='monthly'">
                                                    <template x-for="d in 31" :key="'m-d-'+d">
                                                        <option :value="d" x-text="d"></option>
                                                    </template>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs text-gray-600 mb-1">Time</label>
                                                <input type="time"
                                                       class="w-full border rounded p-2 text-sm"
                                                       x-model="timeStr"
                                                       name="time_monthly"
                                                       :required="form.cycle==='monthly'">
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="form.cycle === 'yearly'">
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                            <div>
                                                <label class="block text-xs text-gray-600 mb-1">Month</label>
                                                <select class="w-full border rounded p-2 text-sm"
                                                        x-model="month"
                                                        name="month_yearly"
                                                        :required="form.cycle==='yearly'">
                                                    <option value="1">January</option>
                                                    <option value="2">February</option>
                                                    <option value="3">March</option>
                                                    <option value="4">April</option>
                                                    <option value="5">May</option>
                                                    <option value="6">June</option>
                                                    <option value="7">July</option>
                                                    <option value="8">August</option>
                                                    <option value="9">September</option>
                                                    <option value="10">October</option>
                                                    <option value="11">November</option>
                                                    <option value="12">December</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs text-gray-600 mb-1">Day</label>
                                                <select class="w-full border rounded p-2 text-sm"
                                                        x-model="dom"
                                                        name="day_yearly"
                                                        :required="form.cycle==='yearly'">
                                                    <template x-for="d in 31" :key="'y-d-'+d">
                                                        <option :value="d" x-text="d"></option>
                                                    </template>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs text-gray-600 mb-1">Time</label>
                                                <input type="time"
                                                       class="w-full border rounded p-2 text-sm"
                                                       x-model="timeStr"
                                                       name="time_yearly"
                                                       :required="form.cycle==='yearly'">
                                            </div>
                                        </div>
                                    </template>

                                    <div class="flex justify-end gap-2 mt-4">
                                        <button type="button" class="px-3 py-1 border rounded text-sm" @click="showEdit=false">Cancel</button>
                                        <button type="submit" class="px-3 py-1 border rounded bg-indigo-600 text-white text-sm" :disabled="loading">Save</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Conflict Modal (Tailwind/Alpine) -->
                        <div x-cloak x-show="showConflict" class="fixed inset-0 z-50 flex items-center justify-center">
                            <div class="absolute inset-0 bg-black/40" @click="closeConflict()"></div>
                            <div class="relative bg-white rounded-lg shadow-lg w-full max-w-md p-5">
                                <h4 class="text-base font-semibold mb-2">Conflicts detected</h4>
                                <p class="text-sm text-gray-600 mb-3">This event overlaps with the following occurrence(s):</p>
                                <div class="max-h-64 overflow-y-auto border rounded p-2 text-xs space-y-2">
                                    <template x-for="(c, idx) in conflicts" :key="'c'+idx">
                                        <div class="border rounded p-2">
                                            <div>
                                                <span class="text-gray-500">Event:</span>
                                                <span class="font-medium" x-text="formatConflictTitle(c)"></span>
                                            </div>
                                            <!-- If backend provided their/yours, show two lines. Otherwise fall back to a single combined line built from start/end/duration. -->
                                            <template x-if="c && (c.their || c.yours)">
                                                <div class="mt-1 space-y-1">
                                                    <div>
                                                        <span class="text-gray-500">Their:</span>
                                                        <span x-text="formatConflictSide(c && c.their)"></span>
                                                        <span> – </span>
                                                        <span x-text="formatConflictSide(c && c.their, 'end')"></span>
                                                    </div>
                                                    <div>
                                                        <span class="text-gray-500">Yours:</span>
                                                        <span x-text="formatConflictSide(c && c.yours)"></span>
                                                        <span> – </span>
                                                        <span x-text="formatConflictSide(c && c.yours, 'end')"></span>
                                                    </div>
                                                </div>
                                            </template>
                                            <template x-if="!(c && (c.their || c.yours))">
                                                <div class="mt-1">
                                                    <span class="text-gray-500">Time:</span>
                                                    <span x-text="formatConflictTime(c)"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                    <div class="px-3 py-2 text-xs text-gray-500" x-show="!conflicts.length">(Conflict details not available)</div>
                                </div>
                                <div class="flex justify-end gap-2 mt-3">
                                    <button type="button" @click="closeConflict()" class="text-sm px-3 py-1 border rounded">Cancel</button>
                                    <button type="button" @click="proceedAnyway()" class="text-sm px-3 py-1 border rounded bg-indigo-600 text-white">Continue anyway</button>
                                </div>
                            </div>
                        </div>

                        <!-- Delete Confirm Modal (Tailwind/Alpine) -->
                        <div x-cloak x-show="showConfirm" class="fixed inset-0 z-50 flex items-center justify-center">
                            <div class="absolute inset-0 bg-black/40" @click="showConfirm=false"></div>
                            <div class="relative bg-white rounded-lg shadow-lg w-full max-w-md p-5">
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="text-base font-semibold">Delete event</h3>
                                    <button class="text-gray-500" @click="showConfirm=false">✕</button>
                                </div>
                                <p class="text-sm text-gray-700">
                                    Are you sure you want to delete
                                    <strong x-text="deleteTitle || 'this event'"></strong>?
                                    This action cannot be undone.
                                </p>
                                <div class="flex justify-end gap-2 mt-4">
                                    <button class="px-3 py-1 border rounded text-sm" @click="showConfirm=false">Cancel</button>
                                    <button class="px-3 py-1 border rounded bg-red-600 text-white text-sm" @click="confirmDeleteModal()">Delete</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function eventsList() {
                return {
                    showEdit: false,
                    showConflict: false,
                    showConfirm: false,
                    showInvite: false,
                    inviteEventId: null,
                    invitees: [],
                    loading: false,
                    errors: {},
                    confirmModal: null,
                    deleteId: null,
                    deleteTitle: '',
                    conflictModal: null,
                    conflicts: [],
                    _pendingUpdate: null,
                    form: {
                        id: null,
                        name: '',
                        description: '',
                        cycle: 'once',
                        duration: 60,
                        date_time: ''
                    },
                    // dynamic inputs for recurrence (match dashboard modal)
                    weekday: 6, // 6=Saturday, 0=Sunday, ... 5=Friday
                    dom: 1, // day of month
                    month: 1, // 1..12
                    timeStr: '09:00',
                    async openInvite(eventId) {
                        this.inviteEventId = eventId;
                        this.showInvite = true;
                        await this.loadInvitees();
                    },
                    closeInvite() {
                        this.showInvite = false;
                        this.inviteEventId = null;
                        this.invitees = [];
                    },
                    async loadInvitees() {
                        if (!this.inviteEventId) return;
                        const url = "{{ route('events.eligible', ['event' => ':id']) }}".replace(':id', String(this.inviteEventId));
                        try {
                            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                            this.invitees = res.ok ? (await res.json()) : [];
                        } catch (e) {
                            console.error('invitees load failed', e);
                            this.invitees = [];
                        }
                    },
                    async sendInvite(contact) {
                        if (!this.inviteEventId || !contact || contact.status !== 'not') return;
                        const url = "{{ route('events.invite', ['event' => ':id']) }}".replace(':id', String(this.inviteEventId));
                        try {
                            const res = await fetch(url, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': this.getCsrf(),
                                },
                                body: JSON.stringify({ contact_id: contact.id })
                            });

                            // Update button state based on response
                            if (res.ok) {
                                contact.status = 'pending';
                            } else if (res.status === 422) {
                                // Probably already invited or validation error → mark as pending
                                contact.status = 'pending';
                            } else if (res.status === 409) {
                                // Already a member → mark as joined
                                contact.status = 'joined';
                            } else {
                                console.error('invite failed', await res.text());
                            }
                        } catch (e) {
                            console.error('invite error', e);
                        }
                    },
                    pad(n) {
                        return String(n).padStart(2, '0');
                    },
                    // compute next occurrence >= now based on cycle & inputs
                    nextOccurrence(cycle) {
                        const now = new Date();
                        const pad = this.pad;
                        const build = (y, m, d, h, mi) => new Date(y, m - 1, d, h, mi, 0, 0);

                        if (cycle === 'once') {
                            if (!this.form.date_time) return null;
                            return new Date(this.form.date_time);
                        }

                        const parts = (this.timeStr || '00:00').split(':');
                        const H = parseInt(parts[0] || '0', 10) || 0;
                        const M = parseInt(parts[1] || '0', 10) || 0;

                        if (cycle === 'daily') {
                            let dt = build(now.getFullYear(), now.getMonth() + 1, now.getDate(), H, M);
                            if (dt < now) dt.setDate(dt.getDate() + 1);
                            return dt;
                        }
                        if (cycle === 'weekly') {
                            // weekday: 0=Sun..6=Sat
                            const target = parseInt(this.weekday, 10) || 0;
                            let dt = build(now.getFullYear(), now.getMonth() + 1, now.getDate(), H, M);
                            const diff = (target - dt.getDay() + 7) % 7;
                            if (diff === 0 && dt < now) dt.setDate(dt.getDate() + 7);
                            else dt.setDate(dt.getDate() + diff);
                            return dt;
                        }
                        if (cycle === 'monthly') {
                            const d = Math.min(28, parseInt(this.dom, 10) || 1);
                            let mSel = parseInt(this.month, 10);
                            if (!mSel || mSel < 1 || mSel > 12) {
                                mSel = now.getMonth() + 1;
                            }
                            let y = now.getFullYear();
                            let dt = build(y, mSel, d, H, M);
                            if (dt < now) {
                                // move one month ahead from the selected month
                                mSel += 1;
                                if (mSel > 12) {
                                    y++;
                                    mSel = 1;
                                }
                                dt = build(y, mSel, d, H, M);
                            }
                            return dt;
                        }
                        if (cycle === 'yearly') {
                            const mo = Math.min(12, Math.max(1, parseInt(this.month, 10) || 1));
                            const d = Math.min(28, Math.max(1, parseInt(this.dom, 10) || 1));
                            let dt = build(now.getFullYear(), mo, d, H, M);
                            if (dt < now) {
                                dt = build(now.getFullYear() + 1, mo, d, H, M);
                            }
                            return dt;
                        }
                        return null;
                    },
                    formatForInput(dt) {
                        if (!dt) return '';
                        const p = this.pad;
                        return `${dt.getFullYear()}-${p(dt.getMonth()+1)}-${p(dt.getDate())}T${p(dt.getHours())}:${p(dt.getMinutes())}`;
                    },
                    // Open edit modal and load event details via AJAX
                    async openEdit(id) {
                        this.errors = {};
                        this.loading = true;
                        this.showEdit = true;
                        try {
                            const res = await fetch(`/events/${id}`, {
                                headers: {
                                    'Accept': 'application/json'
                                }
                            });
                            if (!res.ok) throw new Error('Failed to load event');
                            const ev = await res.json();
                            // Normalize
                            this.form.id = ev.id;
                            this.form.name = ev.name || ev.title || '';
                            this.form.description = ev.description || '';
                            this.form.cycle = ev.cycle || 'once';
                            this.form.duration = ev.duration ?? 60;
                            // date_time -> datetime-local format (no timezone conversion)
                            const dtRaw = (ev.date_time || ev.start || '').toString();
                            if (dtRaw) {
                                // normalize to YYYY-MM-DDTHH:MM
                                const mDate = dtRaw.match(/(\d{4})-(\d{2})-(\d{2})/);
                                const mTime = dtRaw.match(/(?:T|\s)(\d{2}):(\d{2})/);
                                const y = mDate ? mDate[1] : '';
                                const mo = mDate ? mDate[2] : '';
                                const d = mDate ? mDate[3] : '';
                                const hh = mTime ? mTime[1] : '00';
                                const mm = mTime ? mTime[2] : '00';
                                this.form.date_time = (y && mo && d) ? `${y}-${mo}-${d}T${hh}:${mm}` : '';

                                // prefill dynamic fields from the same string (no Date())
                                this.timeStr = `${hh}:${mm}`;
                                this.dom = mDate ? parseInt(d, 10) : 1;
                                this.month = mDate ? parseInt(mo, 10) : 1;
                                // Weekday: compute from local date-only to avoid TZ hour shift
                                if (mDate) {
                                    const wd = new Date(parseInt(y, 10), parseInt(mo, 10) - 1, parseInt(d, 10)).getDay();
                                    this.weekday = wd; // 0=Sun..6=Sat
                                }
                            } else {
                                this.form.date_time = '';
                            }
                        } catch (e) {
                            console.error(e);
                        } finally {
                            this.loading = false;
                        }
                    },
                    async checkConflict(payload) {
                        try {

                            const res = await fetch('/events/check-conflicts', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': this.getCsrf(),
                                },
                                body: JSON.stringify(payload)
                            });
                            if (!res.ok) {
                                return {
                                    conflict: false,
                                    conflicts: []
                                };
                            }
                            const data = await res.json();
                            return {
                                conflict: !!data.conflict,
                                conflicts: data.conflicts || data.collisions || []
                            };
                        } catch (e) {
                            console.error('conflict check failed', e);
                            return {
                                conflict: false,
                                conflicts: []
                            };
                        }
                    },
                    openConflict(conflicts) {
                        const list =
                            Array.isArray(conflicts) ? conflicts :
                            (conflicts && (conflicts.conflicts || conflicts.collisions || conflicts.items || conflicts.events)) || [];
                        this.conflicts = list.filter(Boolean);
                        this.showConflict = true;
                    },
                    closeConflict() {
                        this.showConflict = false;
                        this.conflicts = [];
                    },
                    // ---- Conflict helpers (port from dashboard) ----
                    hmFromMinutes(mins) {
                        if (mins == null || isNaN(mins)) return null;
                        const m = Math.max(0, Math.floor(mins));
                        const h = Math.floor(m / 60);
                        const r = m % 60;
                        return String(h).padStart(2, '0') + ':' + String(r).padStart(2, '0');
                    },
                    formatHM(val) {
                        if (val == null) return null;
                        if (typeof val === 'number') return this.hmFromMinutes(val);
                        const s = String(val).trim();
                        const m = s.match(/(\d{1,2}):(\d{2})/);
                        if (m) {
                            const h = Math.min(23, Math.max(0, parseInt(m[1], 10)));
                            const mi = Math.min(59, Math.max(0, parseInt(m[2], 10)));
                            return String(h).padStart(2, '0') + ':' + String(mi).padStart(2, '0');
                        }
                        const d = new Date(s);
                        if (!isNaN(d)) return String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
                        return null;
                    },
                    normalizeConflictSide(side) {
                        if (!side) return {
                            start: null,
                            end: null
                        };
                        const startRaw = side.start ?? side.startMin ?? side.start_min ?? side.start_minutes;
                        const endRaw = side.end ?? side.endMin ?? side.end_min ?? side.end_minutes;
                        return {
                            start: this.formatHM(startRaw),
                            end: this.formatHM(endRaw)
                        };
                    },
                    formatConflictTitle(c) {
                        if (!c) return 'Event';
                        const t = c.title || c.name || (c.event && (c.event.title || c.event.name));
                        return t || 'Event';
                    },
                    formatConflictSide(side, which = 'start') {
                        if (!side) return '—';
                        const norm = this.normalizeConflictSide(side);
                        return (which === 'end' ? norm.end : norm.start) || '—';
                    },
                    formatConflictTime(c) {
                        if (!c) return '';
                        // Helpers
                        const hm = (ts) => {
                            if (!ts) return '';
                            const s = String(ts);
                            const m = s.match(/(?:T|\s)(\d{2}):(\d{2})/);
                            if (m) return `${m[1]}:${m[2]}`;
                            // Accept already HH:MM
                            if (/^\d{1,2}:\d{2}$/.test(s)) return s.length === 4 ? `0${s}` : s;
                            return '';
                        };
                        const addMin = (hmStr, minutes) => {
                            if (!hmStr || minutes == null) return '';
                            const [h, m] = hmStr.split(':').map(x => parseInt(x, 10) || 0);
                            let t = h * 60 + m + (parseInt(minutes, 10) || 0);
                            t = ((t % 1440) + 1440) % 1440;
                            const hh = String(Math.floor(t / 60)).padStart(2, '0');
                            const mm = String(t % 60).padStart(2, '0');
                            return `${hh}:${mm}`;
                        };

                        // 1) Shape: {their:{start,end}, yours:{start,end}}
                        if ((c.their && (c.their.start || c.their.end)) || (c.yours && (c.yours.start || c.yours.end))) {
                            const tStart = hm(c.their && (c.their.start_time || c.their.start));
                            let tEnd = hm(c.their && (c.their.end_time || c.their.end));
                            if (!tEnd && tStart && c.their && c.their.duration != null) {
                                tEnd = addMin(tStart, c.their.duration);
                            }

                            const yStart = hm(c.yours && (c.yours.start_time || c.yours.start));
                            let yEnd = hm(c.yours && (c.yours.end_time || c.yours.end));
                            if (!yEnd && yStart && c.yours && c.yours.duration != null) {
                                yEnd = addMin(yStart, c.yours.duration);
                            }

                            const theirs = (tStart && tEnd) ? `${tStart} – ${tEnd}` : (tStart || '');
                            const yours = (yStart && yEnd) ? `${yStart} – ${yEnd}` : (yStart || '');

                            if (theirs || yours) {
                                // Show on two lines similar to dashboard style
                                return `Their: ${theirs || '—'} | Yours: ${yours || '—'}`;
                            }
                        }

                        // 2) Shape: {start,end,duration} possibly under c.event
                        const get = (k) => (c[k] ?? (c.event ? c.event[k] : undefined));
                        let start = get('start_time') || get('start') || get('date_time');
                        let end = get('end_time') || get('end');
                        const dur = get('duration');

                        const startHM = hm(start);
                        let endHM = hm(end);
                        if (!endHM && startHM && dur != null) endHM = addMin(startHM, dur);

                        return (startHM && endHM) ? `${startHM} – ${endHM}` : (startHM || '');
                    },
                    // Submit PUT to update
                    async save() {
                        this.errors = {};
                        try {
                            // اگر once نیست، تاریخ شروع رو از ورودی‌های داینامیک بساز
                            if (this.form.cycle !== 'once') {
                                const occ = this.nextOccurrence(this.form.cycle);
                                if (occ) {
                                    this.form.date_time = this.formatForInput(occ);
                                }
                            }
                            const payload = {
                                id: this.form.id,
                                name: this.form.name,
                                description: this.form.description,
                                cycle: this.form.cycle,
                                duration: this.form.duration,
                                date_time: this.form.date_time,
                                exclude_id: this.form.id, // برای نادیده‌گرفتن خود ایونت در چک کانفلیکت
                            };

                            // 1) چک کانفلیکت
                            const chk = await this.checkConflict({
                                cycle: payload.cycle,
                                duration: payload.duration,
                                date_time: payload.date_time,
                                exclude_id: payload.exclude_id,
                            });

                            if (chk.conflict) {
                                this._pendingUpdate = payload;
                                this.openConflict(chk.conflicts);
                                return; // منتظر تأیید کاربر
                            }

                            // 2) بدون تداخل → آپدیت
                            await this.performUpdate(payload);
                        } catch (e) {
                            console.error(e);
                        }
                    },
                    async performUpdate(payload) {
                        try {
                            const res = await fetch(`/events/${this.form.id}${payload && payload.force ? '?force=1' : ''}`, {
                                method: 'PUT',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': this.getCsrf(),
                                },
                                body: JSON.stringify(payload)
                            });

                            // Laravel validation errors
                            if (res.status === 422) {
                                const data = await res.json();
                                this.errors = data.errors || {};
                                return;
                            }

                            // Conflict detected on server (backend double-check)
                            if (res.status === 409) {
                                // If we already forced this request, don't loop—just show a toast/log
                                if (payload && payload.force) {
                                    console.warn('Server still reports conflict even with force flag.');
                                    return;
                                }
                                let data = {};
                                try {
                                    data = await res.json();
                                } catch (_) {}
                                this._pendingUpdate = payload;
                                this.openConflict((data && (data.conflicts || data.collisions)) ? (data.conflicts || data.collisions) : []);
                                return;
                            }

                            if (!res.ok) throw new Error('Failed to update');

                            // Refresh just this row with fresh data
                            const freshRes = await fetch(`/events/${this.form.id}`, {
                                headers: {
                                    'Accept': 'application/json'
                                }
                            });
                            const fresh = await freshRes.json();
                            this.patchRow(fresh);

                            this.showEdit = false;
                            this.closeConflict();
                            this._pendingUpdate = null;
                            this.errors = {};
                        } catch (e) {
                            console.error(e);
                            // Optional: show a toast/alert
                        }
                    },
                    proceedAnyway() {
                        if (!this._pendingUpdate) return;
                        const p = Object.assign({}, this._pendingUpdate, {
                            force: 1
                        });
                        this.performUpdate(p);
                    },
                    askDelete(payload) {
                        this.deleteId = payload?.id || null;
                        this.deleteTitle = payload?.title || '';
                        this.showConfirm = true;
                    },
                    confirmDeleteModal() {
                        if (!this.deleteId) return;
                        this.destroy(this.deleteId).then(() => {
                            this.showConfirm = false;
                            this.deleteId = null;
                            this.deleteTitle = '';
                        }).catch(() => {
                            this.showConfirm = false;
                        });
                    },
                    destroy(id) {
                        const csrf = this.getCsrf();

                        const baseUrl = "{{ route('events.delete', ['event' => ':id']) }}";
                        const url = baseUrl.replace(':id', String(id));

                        const headers = {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrf,
                        };

                        const removeRow = () => {
                            const row = document.getElementById(`ev-row-${id}`);
                            if (row && row.parentNode) row.parentNode.removeChild(row);
                        };

                        return (async () => {
                            try {
                                // Try proper DELETE first
                                let res = await fetch(url, {
                                    method: 'DELETE',
                                    headers
                                });

                                if (res.status === 405 || res.status === 419) {
                                    // Fallback: POST with method spoofing, form-encoded so Laravel parses it reliably
                                    const body = new URLSearchParams();
                                    body.set('_method', 'DELETE');
                                    body.set('_token', csrf);

                                    res = await fetch(url, {
                                        method: 'POST',
                                        headers: {
                                            'Accept': 'application/json',
                                            'X-Requested-With': 'XMLHttpRequest',
                                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                                        },
                                        body,
                                    });
                                }

                                if (!res.ok && res.status !== 204) {
                                    const text = await res.text();
                                    throw new Error(text || `Failed to delete (HTTP ${res.status})`);
                                }

                                removeRow();
                                return true;
                            } catch (e) {
                                console.error('Delete failed:', e);
                                alert('Could not delete the event. Please try again.');
                                throw e;
                            }
                        })();
                    },
                    patchRow(ev) {
                        try {
                            const row = document.getElementById(`ev-row-${ev.id}`);
                            if (!row) return;
                            const title = ev.name || ev.title || '';
                            const desc = ev.description || '';
                            const cycle = (ev.cycle || 'once');
                            // compute time string WITHOUT Date timezone conversions
                            const pad2 = n => String(n).padStart(2, '0');
                            const extractHM = (ts) => {
                                if (!ts) return '';
                                const m = String(ts).match(/(?:T|\s)(\d{2}):(\d{2})/);
                                return m ? `${m[1]}:${m[2]}` : '';
                            };
                            const addMinutesHM = (hm, minutes) => {
                                if (!hm) return '';
                                let [h, m] = hm.split(':').map(x => parseInt(x, 10) || 0);
                                let total = h * 60 + m + (parseInt(minutes, 10) || 0);
                                total = ((total % 1440) + 1440) % 1440;
                                const hh = Math.floor(total / 60),
                                    mm = total % 60;
                                return `${pad2(hh)}:${pad2(mm)}`;
                            };
                            const startHM = extractHM(ev.date_time || ev.start || '');
                            let endHM = extractHM(ev.end || '');
                            if (!endHM && startHM && (ev.duration != null)) {
                                endHM = addMinutesHM(startHM, ev.duration);
                            }
                            const time = (startHM && endHM) ? `${startHM} – ${endHM}` : '—';

                            const q = (sel) => row.querySelector(sel);
                            const set = (sel, val) => {
                                const el = q(sel);
                                if (el) el.textContent = val;
                            };
                            set('.js-title', title);
                            set('.js-desc', desc || '—');
                            set('.js-cycle', (cycle || 'once').charAt(0).toUpperCase() + (cycle || 'once').slice(1));
                            set('.js-time', time);
                        } catch (e) {
                            console.error(e);
                        }
                    },
                    getCsrf() {
                        const m = document.querySelector('meta[name="csrf-token"]');
                        return m ? m.getAttribute('content') : '';
                    },
                    formatHM(d) {
                        if (!d) return '';
                        const pad = n => String(n).padStart(2, '0');
                        return `${pad(d.getHours())}:${pad(d.getMinutes())}`;
                    },
                    renderLocalTimeCell(el) {
                        if (!el) return;
                        const s = el.getAttribute('data-start');
                        const e = el.getAttribute('data-end');
                        if (!s || !e) return; // leave server text
                        const sd = new Date(s);
                        const ed = new Date(e);
                        if (isNaN(sd) || isNaN(ed)) return;
                        el.textContent = `${this.formatHM(sd)} – ${this.formatHM(ed)}`;
                    },
                    convertExistingRows() {
                        document.querySelectorAll('#main-events-table .js-time').forEach(el => this.renderLocalTimeCell(el));
                    },
                    init() {
                        // no-op (show times exactly as stored)
                    },
                }
            }
        </script>
</x-app-layout>
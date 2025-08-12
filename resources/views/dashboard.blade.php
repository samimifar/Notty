<x-app-layout>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                <div class="lg:col-span-3 space-y-6">
                    <div x-data="calendarPanel()" x-init="init()" class="bg-white shadow-sm rounded-lg p-4 h-[560px] flex flex-col">
                        <div class="flex items-center justify-between mb-3">
                            <button @click="prev()" class="px-2 py-1 rounded border text-sm">‹</button>
                            <div class="font-semibold" x-text="title"></div>
                            <button @click="next()" class="px-2 py-1 rounded border text-sm">›</button>
                        </div>
                        <div class="grid grid-cols-7 text-xs text-gray-500 mb-2">
                            <div class="text-center">Sa</div>
                            <div class="text-center">Su</div>
                            <div class="text-center">Mo</div>
                            <div class="text-center">Tu</div>
                            <div class="text-center">We</div>
                            <div class="text-center">Th</div>
                            <div class="text-center">Fr</div>
                        </div>
                        <div class="grid grid-cols-7 gap-1">
                            <template x-for="d in pads" :key="'p'+d">
                                <div class="h-9"></div>
                            </template>
                            <template x-for="d in days" :key="d">
                                <div class="h-9">
                                    <button @click="select(d)" :class="dayClass(d)" class="w-full h-full rounded text-sm">
                                        <span x-text="d"></span>
                                    </button>
                                </div>
                            </template>
                        </div>
                        <br>
                        <!-- <div class="mt-4 border-t pt-4 flex-1 overflow-y-auto pr-2"> -->
                        <div class="space-y-3">
                            <!-- Public Events (box) -->
                            <div>
                                <template x-if="publicEventName">
                                    <div @click="showPublicEventModal=true"
                                        class="h-12 flex items-center p-3 border rounded text-sm text-gray-700 cursor-pointer overflow-hidden">
                                        <div class="whitespace-nowrap overflow-hidden text-ellipsis" x-text="publicEventName"></div>
                                    </div>
                                </template>
                                <template x-if="!publicEventName">
                                    <div class="h-12 flex items-center p-3 border rounded text-sm text-gray-500">No public events</div>
                                </template>
                            </div>

                            <div class="h-px bg-gray-200"></div>

                            <!-- Notes header (same depth as Public Events) -->
                            <div class="flex items-center justify-between">
                                <h3 class="font-semibold">Notes</h3>
                                <template x-if="noteId">
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="openEditor()" class="text-sm px-2 py-1 border rounded">Edit</button>
                                        <form method="POST" :action="`/notes/${noteId}`" x-ref="deleteForm">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" @click="showConfirmation('delete')" class="text-sm px-2 py-1 border rounded text-red-600">Delete</button>
                                        </form>
                                    </div>
                                </template>
                                <template x-if="!noteId">
                                    <button type="button" @click="openCreate()" class="text-sm px-2 py-1 border rounded">Add</button>
                                </template>
                            </div>

                            <!-- Notes body -->
                            <div class="space-y-2">
                                <template x-if="noteId">
                                    <div>
                                        <div @click="showNoteViewModal=true"
                                            class="h-16 flex items-center p-3 border rounded text-sm text-gray-700 cursor-pointer overflow-hidden">
                                            <div class="whitespace-nowrap overflow-hidden text-ellipsis" x-text="text"></div>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="!noteId">
                                    <div class="h-16 flex items-center p-3 border rounded text-sm text-gray-500">No notes for this day</div>
                                </template>
                            </div>
                        </div>
                        <!-- </div> -->

                        <div x-show="showConfirm" x-cloak @keydown.escape.window="showConfirm=false" class="fixed inset-0 z-50 flex items-center justify-center">
                            <div class="absolute inset-0 bg-black/40"></div>
                            <div class="relative bg-white rounded-lg shadow-lg w-full max-w-sm p-5">
                                <h4 class="text-base font-semibold mb-2" x-text="confirmIntent==='delete' ? 'Delete note?' : (confirmIntent==='update' ? 'Save changes?' : 'Save note?')"></h4>
                                <p class="text-sm text-gray-600 mb-4" x-text="confirmIntent==='delete' ? 'This action cannot be undone.' : 'Confirm your action.'"></p>
                                <div class="flex justify-end gap-2">
                                    <button type="button" @click="showConfirm=false" class="px-3 py-1 border rounded">Cancel</button>
                                    <button type="button" class="px-3 py-1 border rounded" :class="confirmIntent==='delete' ? 'text-white bg-red-600 border-red-600' : 'bg-indigo-600 text-white border-indigo-600'"
                                        @click="confirmAction()">
                                        Confirm
                                    </button>
                                </div>
                            </div>
                        </div>
                        <!-- Note View Modal -->
                        <div x-show="showNoteViewModal" x-cloak @keydown.escape.window="showNoteViewModal=false" class="fixed inset-0 z-50 flex items-center justify-center">
                            <div class="absolute inset-0 bg-black/40"></div>
                            <div class="relative bg-white rounded-lg shadow-lg w-full max-w-lg p-5">
                                <h4 class="text-base font-semibold mb-2">Note</h4>
                                <div class="max-h-80 overflow-y-auto p-3 border rounded text-sm whitespace-pre-line" x-text="text"></div>
                                <div class="flex justify-end gap-2 mt-3">
                                    <button type="button" @click="showNoteViewModal=false" class="px-3 py-1 border rounded">Close</button>
                                </div>
                            </div>
                        </div>
                        <!-- Public Event View Modal -->
                        <div x-show="showPublicEventModal" x-cloak @keydown.escape.window="showPublicEventModal=false" class="fixed inset-0 z-50 flex items-center justify-center">
                            <div class="absolute inset-0 bg-black/40"></div>
                            <div class="relative bg-white rounded-lg shadow-lg w-full max-w-lg p-5">
                                <h4 class="text-base font-semibold mb-2">Public Event(s)</h4>
                                <div class="max-h-80 overflow-y-auto p-3 border rounded text-sm whitespace-pre-line" x-text="publicEventName"></div>
                                <div class="flex justify-end gap-2 mt-3">
                                    <button type="button" @click="showPublicEventModal=false" class="px-3 py-1 border rounded">Close</button>
                                </div>
                            </div>
                        </div>
                        <!-- Note Edit Modal -->
                        <div x-show="showNoteEditModal" x-cloak @keydown.escape.window="showNoteEditModal=false" class="fixed inset-0 z-50 flex items-center justify-center">
                            <div class="absolute inset-0 bg-black/40"></div>
                            <div class="relative bg-white rounded-lg shadow-lg w-full max-w-lg p-5">
                                <h4 class="text-base font-semibold mb-2" x-text="noteId ? 'Edit Note' : 'Add Note'"></h4>
                                <form @submit.prevent="saveNote()" class="space-y-3">
                                    <textarea x-model="noteDraft" class="w-full p-3 border rounded text-sm text-gray-800" rows="6" placeholder="Write a note..."></textarea>
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button" @click="showNoteEditModal=false" class="text-sm px-3 py-1 border rounded">Cancel</button>
                                        <button type="submit" class="text-sm px-3 py-1 border rounded bg-indigo-600 text-white">Save</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="lg:col-span-6">
                    <div x-data="weeklySchedule()" x-init="init()" @open-add-event.window="openEventModal()" x-ref="weekly" class="bg-white shadow-sm rounded-lg p-4 h-[560px] flex flex-col">
                        <!-- Title bar -->
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="font-semibold">Weekly Schedule</h3>
                            <button class="text-sm px-2 py-1 border rounded" @click="$dispatch('open-add-event')">Add Event</button>
                        </div>
                        <div class="h-px bg-gray-200 mb-2"></div>
                        <!-- Week navigation -->
                        <div class="flex items-center justify-between mb-2">
                            <button @click="prevWeek" class="px-2 py-1 rounded border text-sm" @click.prevent="prevWeek()">‹</button>
                            <div class="font-semibold text-sm" x-text="weekLabel"></div>
                            <button @click="nextWeek" class="px-2 py-1 rounded border text-sm" @click.prevent="nextWeek()">›</button>
                        </div>

                        <!-- Top hour scale aligned above the RIGHT rail (timeline) -->
                        <div class="mt-1">
                            <div class="grid grid-cols-[120px_1fr] items-center">
                                <!-- left: empty to align with day label column -->
                                <div></div>
                                <!-- right: 24h rail header -->
                                <div class="px-3">
                                    <div class="flex items-center justify-between text-[12px] text-gray-700 select-none">
                                        <template x-for="(lbl, i) in hourLabels">
                                            <span class="tabular-nums" :key="'h'+i" x-text="lbl"></span>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Weekly rows: left big rail (24h) + right day label column -->
                        <div class="mt-1 flex-1 overflow-y-auto" :key="weekKey">
                            <div class="divide-y border rounded">
                                <template x-for="day in days" :key="day.iso">
                                    <div class="grid grid-cols-[120px_1fr]">
                                        <!-- Day label (left) -->
                                        <div class="border-r px-3 flex items-center">
                                            <span class="text-sm font-medium" :class="day.isToday ? 'text-indigo-600' : ''" x-text="day.label"></span>
                                        </div>
                                        <!-- 24h rail (right) -->
                                        <div class="relative h-14">
                                            <!-- hour grid (background) -->
                                            <div class="absolute inset-0 pointer-events-none">
                                                <div class="h-full w-full grid grid-cols-24">
                                                    <template x-for="i in 24" :key="'col'+i">
                                                        <div class="border-r last:border-r-0"></div>
                                                    </template>
                                                </div>
                                            </div>

                                            <!-- absolute-positioned event blocks over the 24h rail -->
                                            <div class="absolute inset-0">
                                            <template x-for="ev in (day.events || [])" :key="'ev'+ev.id">
                                                <div
                                                    class="event-block absolute top-1 bottom-1 rounded px-2 text-[10px] flex items-center shadow-sm truncate cursor-pointer"
                                                    :style="eventBoxStyle(ev)"
                                                    data-bs-toggle="tooltip"
                                                    data-bs-html="true"
                                                    @click.stop="openEventEdit(day.iso, ev)"
                                                    :title="`<strong>${ev.title || ev.name}</strong><br>${ev.start} (${ev.duration_human || formatDuration((ev.endMin ?? 0) - (ev.startMin ?? 0))})<br>${ev.description || ''}`">
                                                    <span class="truncate" x-text="ev.title || ev.name"></span>
                                                </div>
                                            </template>
                                            </div>

                                            <!-- empty-state -->
                                            <template x-if="(!day.events || day.events.length === 0)">
                                                <div class="absolute inset-0 flex items-center justify-center">
                                                    <span class="text-xs text-gray-400">No events</span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Add Event Modal -->
                        <div x-show="showEventModal" x-cloak @keydown.escape.window="showEventModal=false" class="fixed inset-0 z-50 flex items-center justify-center">
                            <div class="absolute inset-0 bg-black/40" @click="showEventModal=false"></div>
                            <div class="relative bg-white rounded-lg shadow-lg w-full max-w-md p-5">
                                <h4 class="text-base font-semibold mb-3">Add Event</h4>
                                <form @submit.prevent="saveEvent()" class="space-y-3">
                                    <div>
                                        <label class="block text-xs font-medium mb-1">Title</label>
                                        <input type="text" x-model="eventForm.name" class="w-full p-2 border rounded text-sm" required>
                                        <p class="text-xs text-red-600 mt-1" x-text="errors.name" x-show="errors.name"></p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium mb-1">Description</label>
                                        <textarea x-model="eventForm.description" class="w-full p-2 border rounded text-sm" rows="2"></textarea>
                                        <p class="text-xs text-red-600 mt-1" x-text="errors.description" x-show="errors.description"></p>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-medium mb-1">Cycle</label>
                                            <select x-model="eventForm.cycle" class="w-full p-2 border rounded text-sm" required>
                                                <option value="once">Once</option>
                                                <option value="daily">Daily</option>
                                                <option value="weekly">Weekly</option>
                                                <option value="monthly">Monthly</option>
                                                <option value="yearly">Yearly</option>
                                            </select>
                                            <p class="text-xs text-red-600 mt-1" x-text="errors.cycle" x-show="errors.cycle"></p>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium mb-1">Duration (minutes)</label>
                                            <input type="number" min="1" max="10080" x-model.number="eventForm.duration" class="w-full p-2 border rounded text-sm" required>
                                            <p class="text-xs text-red-600 mt-1" x-text="errors.duration" x-show="errors.duration"></p>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium mb-1">Start (date & time)</label>
                                        <input type="datetime-local" x-model="eventForm.date_time" class="w-full p-2 border rounded text-sm" required>
                                        <p class="text-xs text-red-600 mt-1" x-text="errors.date_time" x-show="errors.date_time"></p>
                                    </div>

                                    <div class="flex items-center justify-between gap-2 pt-1">
                                        <template x-if="eventForm.id">
                                            <button type="button" @click="deleteEvent(eventForm.id)" class="text-sm px-3 py-1 border rounded text-red-600">Delete</button>
                                        </template>
                                        <div class="ms-auto flex items-center gap-2">
                                            <button type="button" @click="showEventModal=false" class="text-sm px-3 py-1 border rounded">Cancel</button>
                                            <button type="submit" class="text-sm px-3 py-1 border rounded bg-indigo-600 text-white">Save</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="lg:col-span-3">
                    <div x-data="calendarPanel()" x-init="init()" class="bg-white shadow-sm rounded-lg p-4 h-[560px] flex flex-col">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-semibold">Timeline</h3>
                            <button class="text-sm px-2 py-1 border rounded" @click="openTaskModal(null)">Add Task</button>
                        </div>
                        <div class="flex-1 overflow-y-auto pr-2">
                            <ol class="relative border-s ms-3 space-y-6">
                                <template x-for="(task, idx) in tasks" :key="task.id">
                                    <li class="ms-4 cursor-pointer" @click="openTaskModal(task)" :class="task.status ? 'text-gray-400' : ''">
                                        <!-- clickable timeline dot (same size/position) -->
                                        <button @click.stop="toggleTaskStatus(task)"
                                            class="absolute w-3 h-3 bg-white border rounded-full -start-1.5 mt-1.5 grid place-items-center"
                                            :class="task.status ? 'border-green-600' : 'border-gray-400'"
                                            :title="task.status ? 'Mark as pending' : 'Mark as completed'">
                                            <!-- tiny green check when completed (keeps exact dot size) -->
                                            <svg x-show="task.status" x-cloak viewBox="0 0 12 12" class="w-2.5 h-2.5 text-green-600">
                                                <path fill="currentColor" d="M10.3 3.3a1 1 0 0 1 0 1.4L5.5 9.5a1 1 0 0 1-1.4 0L1.7 7.1a1 1 0 1 1 1.4-1.4l1.7 1.7 4.1-4.1a1 1 0 0 1 1.4 0z" />
                                            </svg>
                                        </button>

                                        <h4 class="text-sm font-medium" x-text="task.name + (task.remainingString ? ' (' + task.remainingString + ')' : '')"></h4>
                                        <p class="text-xs text-gray-500" x-text="task.description"></p>
                                    </li>
                                </template>
                                <template x-if="tasks.length === 0">
                                    <li class="ms-4">
                                        <div class="absolute w-3 h-3 bg-white border rounded-full -start-1.5 mt-1.5"></div>
                                        <h4 class="text-sm font-medium text-gray-400">No tasks</h4>
                                    </li>
                                </template>
                            </ol>
                        </div>
                        <!-- Task Modal -->
                        <div x-show="showTaskModal" x-cloak @keydown.escape.window="showTaskModal=false" class="fixed inset-0 z-50 flex items-center justify-center">
                            <div class="absolute inset-0 bg-black/40"></div>
                            <div class="relative bg-white rounded-lg shadow-lg w-full max-w-sm p-5">
                                <h4 class="text-base font-semibold mb-2" x-text="taskForm.id ? 'Edit Task' : 'Add Task'"></h4>
                                <form @submit.prevent="saveTask()" class="space-y-3">
                                    <div>
                                        <label class="block text-xs font-medium mb-1">Name</label>
                                        <input type="text" x-model="taskForm.name" class="w-full p-2 border rounded text-sm" required>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium mb-1">Description</label>
                                        <textarea x-model="taskForm.description" class="w-full p-2 border rounded text-sm" rows="2"></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium mb-1">Deadline</label>
                                        <input type="datetime-local" x-model="taskForm.deadline" class="w-full p-2 border rounded text-sm" required>
                                    </div>
                                    <div class="flex items-center justify-between gap-2">
                                        <template x-if="taskForm.id">
                                            <button type="button" class="text-sm px-3 py-1 border rounded text-red-600" @click="deleteTask(taskForm.id)">Delete</button>
                                        </template>
                                        <div class="ms-auto flex items-center gap-2">
                                            <button type="button" @click="showTaskModal=false" class="text-sm px-3 py-1 border rounded">Cancel</button>
                                            <button type="submit" class="text-sm px-3 py-1 border rounded bg-indigo-600 text-white">Save</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        // Bootstrap tooltip activation
        document.addEventListener("DOMContentLoaded", function(){
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            tooltipTriggerList.map(function (tooltipTriggerEl) {
              return new bootstrap.Tooltip(tooltipTriggerEl)
            })
        });

        function calendarPanel() {
            return {
                today: new Date(),
                y: null,
                m: null,
                days: [],
                pads: [],
                title: '',
                selected: null,
                selectedIso: '',
                noteId: null,
                text: '',
                noteDraft: '',
                publicEventName: '',
                open: false,
                showConfirm: false,
                confirmIntent: null,
                showNoteViewModal: false,
                showNoteEditModal: false,
                showPublicEventModal: false,
                // Tasks
                tasks: [],
                showTaskModal: false,
                taskForm: {
                    id: null,
                    name: '',
                    description: '',
                    deadline: ''
                },
                init() {
                    const t = this.today;
                    this.y = t.getFullYear();
                    this.m = t.getMonth();
                    this.selected = new Date(t.getFullYear(), t.getMonth(), t.getDate());
                    this.selectedIso = this.iso(this.selected);
                    this.build();
                    this.fetchDay();
                    this.fetchTasks();
                },
                build() {
                    const first = new Date(this.y, this.m, 1);
                    const last = new Date(this.y, this.m + 1, 0);
                    this.title = first.toLocaleString('en-US', {
                        month: 'long',
                        year: 'numeric'
                    });
                    this.pads = [...Array(this.satFirst(first.getDay())).keys()];
                    this.days = [...Array(last.getDate()).keys()].map(i => i + 1);
                },
                satFirst(d) {
                    // JS getDay(): 0=Sun..6=Sat; convert to Saturday-first index where 0=Sat
                    return (d + 1) % 7;
                },
                prev() {
                    if (this.m === 0) {
                        this.m = 11;
                        this.y--;
                    } else {
                        this.m--;
                    }
                    this.build();
                },
                next() {
                    if (this.m === 11) {
                        this.m = 0;
                        this.y++;
                    } else {
                        this.m++;
                    }
                    this.build();
                },
                select(d) {
                    this.selected = new Date(this.y, this.m, d);
                    this.selectedIso = this.iso(this.selected);
                    this.fetchDay();
                },
                dayClass(d) {
                    const isToday = d === this.today.getDate() && this.m === this.today.getMonth() && this.y === this.today.getFullYear();
                    const isSelected = this.selected && d === this.selected.getDate() && this.m === this.selected.getMonth() && this.y === this.selected.getFullYear();
                    let base = 'hover:bg-gray-100';
                    if (isSelected) {
                        base = 'bg-indigo-600 text-white';
                    }
                    if (isToday) {
                        base += ' border-2 border-indigo-600';
                    }
                    return base;
                },
                iso(dt) {
                    const y = dt.getFullYear();
                    const m = String(dt.getMonth() + 1).padStart(2, '0');
                    const d = String(dt.getDate()).padStart(2, '0');
                    return `${y}-${m}-${d}`;
                },
                async fetchDay() {
                    try {
                        const res = await fetch(`{{ route('dashboard.day') }}?date=${this.selectedIso}`, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        const data = await res.json();
                        if (data.note) {
                            this.noteId = data.note.id;
                            this.text = data.note.text || '';
                        } else {
                            this.noteId = null;
                            this.text = '';
                        }
                        this.publicEventName = data.publicEvent ? (data.publicEvent.name || '') : '';
                        this.open = false;
                        this.showConfirm = false;
                        this.confirmIntent = null;
                    } catch (e) {
                        console.error(e);
                    }
                },
                openEditor() {
                    this.noteDraft = this.text || '';
                    this.showNoteEditModal = true;
                },
                openCreate() {
                    this.noteDraft = '';
                    this.showNoteEditModal = true;
                },
                cancelEdit() {
                    this.open = false;
                },
                async confirmAction() {
                    try {
                        if (this.confirmIntent === 'delete') {
                            await this.deleteNote();
                        } else {
                            await this.saveNote();
                        }
                    } finally {
                        this.showConfirm = false;
                        this.showNoteEditModal = false;
                        this.showNoteViewModal = false;
                    }
                },
                async saveNote() {
                    const url = this.noteId ? `/notes/${this.noteId}` : `{{ route('notes.store') }}`;
                    const method = this.noteId ? 'PUT' : 'POST';
                    const payload = this.noteId ? {
                        text: this.noteDraft
                    } : {
                        date: this.selectedIso,
                        text: this.noteDraft
                    };
                    const res = await fetch(url, {
                        method,
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': this.getCsrf(),
                        },
                        body: JSON.stringify(payload)
                    });
                    if (!res.ok) {
                        throw new Error('Failed to save note');
                    }
                    await res.json();
                    await this.fetchDay();
                    this.showNoteEditModal = false;
                    this.open = false;
                },
                async deleteNote() {
                    if (!this.noteId) return;
                    const url = `/notes/${this.noteId}`;
                    const res = await fetch(url, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': this.getCsrf(),
                        }
                    });
                    if (!res.ok) {
                        throw new Error('Failed to delete note');
                    }
                    this.noteId = null;
                    this.text = '';
                    this.open = false;
                },
                getCsrf() {
                    const el = document.querySelector('meta[name="csrf-token"]');
                    return el ? el.getAttribute('content') : '';
                },
                showConfirmation(intent) {
                    this.confirmIntent = intent;
                    this.showConfirm = true;
                },
                async fetchTasks() {
                    try {
                        const res = await fetch('/tasks', {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });
                        if (!res.ok) throw new Error('Failed to fetch tasks');
                        let data = await res.json();
                        const now = new Date();
                        this.tasks = data.map(task => {
                            let remaining = null,
                                remainingString = '';
                            if (task.deadline) {
                                const deadline = new Date(task.deadline);
                                remaining = deadline - now;
                                if (remaining > 0) {
                                    const sec = Math.floor(remaining / 1000);
                                    const min = Math.floor(sec / 60);
                                    const hr = Math.floor(min / 60);
                                    const day = Math.floor(hr / 24);
                                    if (day > 0) remainingString = `${day}d`;
                                    else if (hr > 0) remainingString = `${hr % 24}h`;
                                    else if (min > 0) remainingString = `${min % 60}m`;
                                    else remainingString = `${sec % 60}s`;
                                } else {
                                    remainingString = 'Overdue';
                                }
                            }
                            const statusNum = task.status ? 1 : 0;
                            return {
                                ...task,
                                status: statusNum,
                                remaining,
                                remainingString
                            };
                        }).sort((a, b) => {
                            if (a.remaining == null) return 1;
                            if (b.remaining == null) return -1;
                            return a.remaining - b.remaining;
                        });
                    } catch (e) {
                        console.error(e);
                        this.tasks = [];
                    }
                },
                openTaskModal(task) {
                    if (task) {
                        this.taskForm = {
                            id: task.id,
                            name: task.name || '',
                            description: task.description || '',
                            deadline: task.deadline ? task.deadline.slice(0, 16) : ''
                        };
                    } else {
                        this.taskForm = {
                            id: null,
                            name: '',
                            description: '',
                            deadline: ''
                        };
                    }
                    this.showTaskModal = true;
                },
                async saveTask() {
                    try {
                        const url = this.taskForm.id ? `/tasks/${this.taskForm.id}` : '/tasks';
                        const method = this.taskForm.id ? 'PUT' : 'POST';
                        const payload = {
                            name: this.taskForm.name,
                            description: this.taskForm.description,
                            deadline: this.taskForm.deadline
                        };
                        const res = await fetch(url, {
                            method: method,
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': this.getCsrf(),
                            },
                            body: JSON.stringify(payload)
                        });
                        if (!res.ok) throw new Error('Failed to save task');
                        this.showTaskModal = false;
                        await this.fetchTasks();
                    } catch (e) {
                        alert('Error saving task');
                        console.error(e);
                    }
                },
                async deleteTask(id) {
                    if (!id) return;
                    if (!confirm('Delete this task?')) return;
                    try {
                        const res = await fetch(`/tasks/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': this.getCsrf()
                            }
                        });
                        if (!res.ok) throw new Error('Failed to delete task');
                        this.showTaskModal = false;
                        await this.fetchTasks();
                    } catch (e) {
                        console.error(e);
                    }
                },
                async toggleTaskStatus(task) {
                    if (!task) return;
                    try {
                        const newStatus = task.status ? 0 : 1;
                        const res = await fetch(`/tasks/${task.id}`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': this.getCsrf(),
                            },
                            body: JSON.stringify({
                                status: newStatus,
                                name: task.name,
                                description: task.description,
                                deadline: task.deadline
                            })
                        });
                        if (!res.ok) throw new Error('Failed to toggle status');
                        await this.fetchTasks();
                    } catch (e) {
                        console.error(e);
                    }
                },
            }
        }
        // Weekly Schedule (center area) — UI only, no backend yet
        function weeklySchedule() {
            const pad = n => n < 10 ? '0' + n : n;
            const toISO = d => d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
            const startOfWeekSat = (date) => {
                const d = new Date(date);
                const jsDow = d.getDay(); // 0=Sun..6=Sat
                const offset = jsDow === 6 ? 0 : jsDow + 1; // Sat->0, Sun->1, ... Fri->6
                d.setDate(d.getDate() - offset);
                d.setHours(0, 0, 0, 0);
                return d;
            };
            const monthShort = (d) => d.toLocaleString('en-US', {
                month: 'short'
            });
            const weekdayShort = ['Sat', 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'];
            return {
                anchor: new Date(), // any day in current week
                days: [],
                weekLabel: '',
                weekKey: 0,
                version: 0,
                hourLabels: ['00','03','06','09','12','15','18','21','00'],
                hoursHeader: Array.from({
                    length: 25
                }, (_, i) => (24 - i) % 24),
                busy: false,
                showEventModal: false,
                eventForm: {
                    name: '',
                    description: '',
                    cycle: 'once',
                    duration: 60,
                    date_time: ''
                },
                errors: {},
                // --- helpers to normalize backend payloads into HH:MM ---
                pad2(n){ return (n<10?'0':'')+n; },
                hmFromDateString(dt){
                    if(!dt) return '00:00';
                    const s = String(dt).trim();
                    // try direct HH:MM first
                    let m = s.match(/\b(\d{1,2}):(\d{2})\b/);
                    if(m){
                        const h = Math.min(23, Math.max(0, parseInt(m[1],10)));
                        const mi = Math.min(59, Math.max(0, parseInt(m[2],10)));
                        return this.pad2(h)+":"+this.pad2(mi);
                    }
                    // try Date parsing as a fallback
                    try{
                        const d = new Date(s);
                        if(!isNaN(d)) return this.pad2(d.getHours())+":"+this.pad2(d.getMinutes());
                    }catch(_){/* ignore */}
                    return '00:00';
                },
                normalizeEvent(e){
                    const DAY = 1440;
                    const clamp = (n) => Math.max(0, Math.min(DAY, n));
                    // 1) explicit minutes since midnight
                    if(e.start_minutes != null || e.start_min != null){
                        const sm = parseInt(e.start_minutes ?? e.start_min, 10) || 0;
                        const dur = parseInt(e.duration ?? e.duration_minutes ?? e.duration_mins ?? 60, 10) || 60;
                        const em = clamp(sm + dur);
                        return {
                            id: e.id,
                            title: e.name || e.title || 'event',
                            start: this.pad2(Math.floor(sm/60))+":"+this.pad2(sm%60),
                            end: this.pad2(Math.floor(em/60))+":"+this.pad2(em%60),
                            startMin: sm,
                            endMin: em,
                            color: e.color || null,
                            description: (e.description || ''),
                            cycle: (e.cycle || 'once'),
                        };
                    }
                    // 2) explicit start/end strings
                    if(e.start || e.starts_at || e.start_time){
                        const startHM = this.hmFromDateString(e.start || e.starts_at || e.start_time);
                        const [sh, sm] = startHM.split(':').map(v=>parseInt(v,10));
                        const startMin = clamp((sh*60 + sm) || 0);
                        let endHM = e.end || e.ends_at || e.end_time ? this.hmFromDateString(e.end || e.ends_at || e.end_time) : null;
                        let endMin;
                        if(endHM){
                            const [eh, em] = endHM.split(':').map(v=>parseInt(v,10));
                            endMin = clamp((eh*60 + em) || startMin);
                        } else {
                            const dur = parseInt(e.duration ?? e.duration_minutes ?? e.duration_mins ?? 60, 10) || 60;
                            endMin = clamp(startMin + dur);
                            endHM = this.pad2(Math.floor(endMin/60))+":"+this.pad2(endMin%60);
                        }
                        return {
                            id: e.id,
                            title: e.name || e.title || 'event',
                            start: startHM,
                            end: endHM,
                            startMin,
                            endMin,
                            color: e.color || null,
                            description: (e.description || ''),
                            cycle: (e.cycle || 'once'),
                        };
                    }
                    // 3) date_time + duration
                    const startHM3 = this.hmFromDateString(e.date_time || e.startsAt || e.startsAtLocal || e.datetime);
                    const [sh3, sm3] = startHM3.split(':').map(v=>parseInt(v,10));
                    const startMin3 = clamp((sh3*60 + sm3) || 0);
                    const dur3 = parseInt(e.duration ?? e.duration_minutes ?? e.duration_mins ?? 60, 10) || 60;
                    const endMin3 = clamp(startMin3 + dur3);
                    const endHM3 = this.pad2(Math.floor(endMin3/60))+":"+this.pad2(endMin3%60);
                    return {
                        id: e.id,
                        title: e.name || e.title || 'event',
                        start: startHM3,
                        end: endHM3,
                        startMin: startMin3,
                        endMin: endMin3,
                        color: e.color || null,
                        description: (e.description || ''),
                        cycle: (e.cycle || 'once'),
                    };
                },
                formatDuration(mins){
                    const m = Math.max(0, Math.floor(mins || 0));
                    if (m >= 60){
                        const h = Math.floor(m/60);
                        const r = m % 60;
                        return r ? `${h}h ${r}m` : `${h}h`;
                    }
                    return `${m} min`;
                },
                initTooltips(){
                    try{
                        const root = this.$refs && this.$refs.weekly ? this.$refs.weekly : document;
                        const els = root.querySelectorAll('[data-bs-toggle="tooltip"]');
                        els.forEach(el => {
                            if (window.bootstrap && window.bootstrap.Tooltip){
                                const inst = window.bootstrap.Tooltip.getInstance(el);
                                if (inst) inst.dispose();
                                new window.bootstrap.Tooltip(el);
                            }
                        });
                    }catch(e){ console.error(e); }
                },
                eventBoxStyle(ev){
                    const DAY = 1440;
                    const left = (ev.startMin / DAY) * 100;
                    const width = Math.max(0.8, ((ev.endMin - ev.startMin) / DAY) * 100);
                    return {
                        left: left + '%',
                        width: width + '%'
                    };
                },
                async init() {
                    await this.build();
                },
                async build() {
                    const start = startOfWeekSat(this.anchor);
                    const arr = [];
                    for (let i = 0; i < 7; i++) {
                        const dt = new Date(start);
                        dt.setDate(start.getDate() + i);
                        arr.push({
                            iso: toISO(dt),
                            label: `${dt.getDate()} ${dt.toLocaleString('en-US',{month:'short'})}`,
                            isToday: (new Date().toDateString() === dt.toDateString()),
                            events: []
                        });
                    }
                    this.days = arr;
                    const end = new Date(start);
                    end.setDate(start.getDate() + 6);
                    this.weekLabel = `${monthShort(start)} ${start.getDate()} – ${monthShort(end)} ${end.getDate()}, ${end.getFullYear()}`;
                    this.weekKey = `${toISO(start)}:${this.version}`; // forces full re-render of the rows
                    await this.fetchWeek();
                    await this.$nextTick();
                    this.initTooltips();
                },
                async prevWeek() {
                    if (this.busy) return;
                    this.busy = true;
                    this.anchor.setDate(this.anchor.getDate() - 7);
                    this.version++;
                    await this.build();
                    this.busy = false;
                },
                async nextWeek() {
                    if (this.busy) return;
                    this.busy = true;
                    this.anchor.setDate(this.anchor.getDate() + 7);
                    this.version++;
                    await this.build();
                    this.busy = false;
                },
                // Convert "HH:MM" or minutes to minutes since midnight
                hmToMin(s){
                    if(s==null) return null;
                    if(typeof s === 'number') return s;
                    const m = String(s).match(/(\d{1,2}):(\d{2})/);
                    if(m){
                        const h = Math.min(23, Math.max(0, parseInt(m[1],10)));
                        const mi = Math.min(59, Math.max(0, parseInt(m[2],10)));
                        return h*60 + mi;
                    }
                    return null;
                },
                weekStartISO() {
                    const d = new Date(this.anchor);
                    const jsDow = d.getDay(); // 0..6
                    const offset = (jsDow === 6) ? 0 : jsDow + 1; // هفته از شنبه
                    d.setDate(d.getDate() - offset);
                    d.setHours(0, 0, 0, 0);
                    const p = n => n < 10 ? '0' + n : n;
                    return `${d.getFullYear()}-${p(d.getMonth()+1)}-${p(d.getDate())}`;
                },
                async fetchWeek() {
                    try {
                        const startIso = this.weekStartISO();
                        const res = await fetch(`/events/week?start=${startIso}`, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });
                        if (!res.ok) throw new Error('Failed to fetch week events');
                        const data = await res.json();
                        const byDate = {};
                        (data.days || []).forEach(d => {
                            const arr = Array.isArray(d.events) ? d.events.map(ev=>this.normalizeEvent(ev)) : [];
                            byDate[d.date] = arr;
                        });
                        this.days = this.days.map(d => ({
                            ...d,
                            events: (byDate[d.iso] || [])
                        }));
                        await this.$nextTick();
                        this.initTooltips();
                    } catch (e) {
                        console.error(e);
                    }
                },
                openEventModal() {
                    this.errors = {};
                    // default start = selected week's start day at 09:00
                    const d = (() => {
                        const s = new Date(this.anchor);
                        const js = s.getDay();
                        const off = (js === 6) ? 0 : js + 1;
                        s.setDate(s.getDate() - off);
                        s.setHours(9, 0, 0, 0);
                        return s;
                    })();
                    const p = n => n < 10 ? '0' + n : n;
                    const dtLocal = `${d.getFullYear()}-${p(d.getMonth()+1)}-${p(d.getDate())}T${p(d.getHours())}:${p(d.getMinutes())}`;
                    this.eventForm = {
                        name: '',
                        description: '',
                        cycle: 'once',
                        duration: 60,
                        date_time: dtLocal
                    };
                    this.showEventModal = true;
                },
                openEventEdit(dayIso, ev){
                    this.errors = {};
                    // optimistic: open modal with a lightweight placeholder while loading
                    this.showEventModal = true;
                    this.eventForm = {
                        id: ev.id,
                        name: '',
                        description: '',
                        cycle: 'once',
                        duration: 60,
                        date_time: `${dayIso}T00:00`
                    };

                    const p = n => n < 10 ? '0' + n : n;
                    const toLocalInput = (dStr, fallbackHM = '00:00') => {
                        if (!dStr) return `${dayIso}T${fallbackHM}`;
                        const d = new Date(dStr);
                        if (!isNaN(d)) {
                            return `${d.getFullYear()}-${p(d.getMonth()+1)}-${p(d.getDate())}T${p(d.getHours())}:${p(d.getMinutes())}`;
                        }
                        // if server sends only HH:MM or unparsable date, fall back to selected day + given time
                        const m = String(dStr).match(/(\d{1,2}):(\d{2})/);
                        if (m){
                            const hh = p(Math.min(23, Math.max(0, parseInt(m[1],10))));
                            const mm = p(Math.min(59, Math.max(0, parseInt(m[2],10))));
                            return `${dayIso}T${hh}:${mm}`;
                        }
                        return `${dayIso}T${fallbackHM}`;
                    };

                    fetch(`/events/${ev.id}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(res => {
                        if (!res.ok) throw new Error('Failed to load event');
                        return res.json();
                    })
                    .then(data => {
                        // duration in minutes — prefer explicit value; otherwise derive if start/end provided
                        let duration = 60;
                        if (data.duration != null) {
                            duration = parseInt(data.duration, 10) || 60;
                        } else if (data.endMin != null && data.startMin != null) {
                            duration = Math.max(1, parseInt(data.endMin,10) - parseInt(data.startMin,10));
                        }

                        this.eventForm = {
                            id: data.id,
                            name: data.name || data.title || '',
                            description: data.description || '',
                            cycle: data.cycle || 'once',
                            duration: duration,
                            date_time: toLocalInput(data.date_time || data.starts_at || data.start)
                        };
                    })
                    .catch(err => {
                        console.error(err);
                        // keep modal open with the lightweight placeholder so the user can still edit if needed
                    });
                },
                getCsrf() {
                    const el = document.querySelector('meta[name="csrf-token"]');
                    return el ? el.getAttribute('content') : '';
                },
                async saveEvent() {
                    this.errors = {};
                    try {
                        const isEdit = !!this.eventForm.id;
                        const url = isEdit ? `/events/${this.eventForm.id}` : '/events';
                        const method = isEdit ? 'PUT' : 'POST';
                        const res = await fetch(url, {
                            method,
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': this.getCsrf(),
                            },
                            body: JSON.stringify(this.eventForm)
                        });
                        if (res.status === 422) {
                            const data = await res.json();
                            this.errors = data.errors || {};
                            return;
                        }
                        if (!res.ok) throw new Error('Failed to save event');
                        this.showEventModal = false;
                        await this.fetchWeek();
                        await this.$nextTick();
                        this.initTooltips();
                    } catch (e) {
                        console.error(e);
                    }
                },
                async deleteEvent(id){
                    if(!id) return;
                    try{
                        const res = await fetch(`/events/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': this.getCsrf(),
                            }
                        });
                        if(!res.ok) throw new Error('Failed to delete event');
                        this.showEventModal = false;
                        await this.fetchWeek();
                        await this.$nextTick();
                        this.initTooltips();
                    }catch(e){ console.error(e); }
                }
            }
        }
    </script>
<style>
    /* Weekly schedule event styling to match monthly calendar blue */
    .event-block {
        height: calc(100% - 0.5rem);
        background-color: rgba(79, 70, 229, 0.30); /* indigo-600 @ 30% */
        border: 1px solid #4f46e5;                 /* indigo-600 */
        color: #111827;                             /* slate-900 for readable text */
        border-radius: 0.5rem;                      /* 8px */
    }

    /* Slight emphasis on hover */
    .event-block:hover {
        background-color: rgba(79, 70, 229, 0.40);
        border-color: #4338ca; /* indigo-700 */
    }
</style>
</x-app-layout>
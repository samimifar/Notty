<x-app-layout>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-sm text-gray-500">Users</div>
                    <div class="text-2xl font-bold mt-1">{{ number_format($stats['users']) }}</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-sm text-gray-500">Events</div>
                    <div class="text-2xl font-bold mt-1">{{ number_format($stats['events']) }}</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-sm text-gray-500">Tasks</div>
                    <div class="text-2xl font-bold mt-1">{{ number_format($stats['tasks']) }}</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-sm text-gray-500">Notes</div>
                    <div class="text-2xl font-bold mt-1">{{ number_format($stats['notes']) }}</div>
                </div>
            </div>

            {{-- Table --}}
            <div class="bg-white rounded-lg shadow">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left font-medium text-gray-600">User</th>
                                <th class="px-4 py-2 text-center font-medium text-gray-600">Events</th>
                                <th class="px-4 py-2 text-center font-medium text-gray-600">Tasks</th>
                                <th class="px-4 py-2 text-center font-medium text-gray-600">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($perUser as $u)
                                <tr>
                                    <td class="px-4 py-2">
                                        <div class="font-medium">{{ $u->name ?? '—' }}</div>
                                        <div class="text-gray-500 text-xs">{{ $u->phone ?? $u->email ?? '' }}</div>
                                    </td>
                                    <td class="px-4 py-2 text-center">{{ $u->events_count }}</td>
                                    <td class="px-4 py-2 text-center">{{ $u->tasks_count }}</td>
                                    <td class="px-4 py-2 text-center">{{ $u->notes_count }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3 border-t">
                    {{ $perUser->links() }}
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
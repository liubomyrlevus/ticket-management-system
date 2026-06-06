<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Tickets') }}
            </h2>
            @if(auth()->user()->role === 'admin' || auth()->user()->role === 'staff' || auth()->user()->is_approved)
                <a href="{{ route('tickets.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    + Create Ticket
                </a>
            @else
                <p class="text-gray-500 italic">Your account is pending admin approval.</p>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <form action="{{ route('tickets.index') }}" method="GET" class="mb-4 bg-gray-50 py-2 px-4 rounded-md border border-gray-200">
                        @foreach(request()->except(['date_from', 'date_to', 'page']) as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach
                        <div class="flex flex-wrap items-end gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-0.5">Date From</label>
                                <input type="date" name="date_from" value="{{ request('date_from') }}" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm py-1 px-2">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-0.5">Date To</label>
                                <input type="date" name="date_to" value="{{ request('date_to') }}" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm py-1 px-2">
                            </div>
                            <div class="flex gap-2">
                                <button type="submit" class="px-3 py-1 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 text-sm transition">Filter</button>
                                <a href="{{ route('tickets.index') }}" class="px-3 py-1 bg-white text-gray-700 border border-gray-300 rounded-md hover:bg-gray-50 text-sm transition">Reset</a>
                            </div>
                        </div>
                    </form>

                    <div class="mb-4 flex flex-wrap items-center gap-5 bg-gray-50 py-2 px-4 rounded-md border border-gray-200 text-sm">
                        <div class="flex items-center gap-3">
                            <span class="font-bold text-gray-700">STATS:</span>
                            <span class="px-2 py-0.5 bg-yellow-100 text-yellow-800 rounded border border-yellow-200 shadow-sm">Pending: {{ $pendingTasks }}</span>
                            <span class="px-2 py-0.5 bg-green-100 text-green-800 rounded border border-green-200 shadow-sm">Resolved: {{ $resolvedTasks }}</span>
                        </div>

                        @if($categorySummary->isNotEmpty())
                            <span class="text-gray-300">|</span>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-gray-700 me-1">CATEGORY:</span>
                                @foreach($categorySummary as $summary)
                                    <span class="px-2 py-0.5 bg-white text-gray-700 rounded border border-gray-300 shadow-sm">
                                        {{ $summary->category->name ?? 'Uncategorized' }}: {{ $summary->total }}
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        @if($performance && $performance->avg_time)
                            <span class="text-gray-300">|</span>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-gray-700 me-1">TIME (hrs):</span>
                                <span class="px-2 py-0.5 bg-blue-50 text-blue-800 rounded border border-blue-200 shadow-sm">Avg: {{ round($performance->avg_time / 3600, 1) }}</span>
                                <span class="px-2 py-0.5 bg-blue-50 text-blue-800 rounded border border-blue-200 shadow-sm">Min: {{ round($performance->min_time / 3600, 1) }}</span>
                                <span class="px-2 py-0.5 bg-blue-50 text-blue-800 rounded border border-blue-200 shadow-sm">Max: {{ round($performance->max_time / 3600, 1) }}</span>
                            </div>
                        @endif
                    </div>

                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr>
                                <th class="border-b-2 py-2 px-4">ID</th>
                                <th class="border-b-2 py-2 px-4">Title</th>
                                
                                <th class="border-b-2 py-2 px-4 text-left relative" x-data="{ open: false }">
                                    <button @click="open = !open" @click.away="open = false" class="flex items-center gap-1 hover:text-indigo-600 focus:outline-none font-bold uppercase text-xs">
                                        Category
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                        @if(request('category')) <span class="w-2 h-2 bg-indigo-500 rounded-full"></span> @endif
                                    </button>
                                    <div x-show="open" style="display: none;" class="absolute z-50 mt-1 w-48 bg-white rounded-md shadow-lg border border-gray-200 py-1 font-normal text-sm normal-case whitespace-nowrap">
                                        <a href="{{ request()->fullUrlWithQuery(['category' => null, 'page' => null]) }}" class="block px-4 py-2 hover:bg-gray-100 {{ !request('category') ? 'font-bold text-indigo-600' : 'text-gray-700' }}">All Categories</a>
                                        @foreach($categories as $category)
                                            <a href="{{ request()->fullUrlWithQuery(['category' => $category->id, 'page' => null]) }}" class="block px-4 py-2 hover:bg-gray-100 {{ request('category') == $category->id ? 'font-bold text-indigo-600' : 'text-gray-700' }}">
                                                {{ $category->name }}
                                            </a>
                                        @endforeach
                                    </div>
                                </th>

                                <th class="border-b-2 py-2 px-4 text-left relative" x-data="{ open: false }">
                                    <button @click="open = !open" @click.away="open = false" class="flex items-center gap-1 hover:text-indigo-600 focus:outline-none font-bold uppercase text-xs">
                                        Priority
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                        @if(request('priority')) <span class="w-2 h-2 bg-indigo-500 rounded-full"></span> @endif
                                    </button>
                                    <div x-show="open" style="display: none;" class="absolute z-50 mt-1 w-40 bg-white rounded-md shadow-lg border border-gray-200 py-1 font-normal text-sm normal-case whitespace-nowrap">
                                        <a href="{{ request()->fullUrlWithQuery(['priority' => null, 'page' => null]) }}" class="block px-4 py-2 hover:bg-gray-100 {{ !request('priority') ? 'font-bold text-indigo-600' : 'text-gray-700' }}">All Priorities</a>
                                        @foreach($priorities as $priority)
                                            <a href="{{ request()->fullUrlWithQuery(['priority' => $priority->id, 'page' => null]) }}" class="block px-4 py-2 hover:bg-gray-100 {{ request('priority') == $priority->id ? 'font-bold text-indigo-600' : 'text-gray-700' }}">
                                                {{ $priority->name }}
                                            </a>
                                        @endforeach
                                    </div>
                                </th>

                                <th class="border-b-2 py-2 px-4 text-left relative" x-data="{ open: false }">
                                    <button @click="open = !open" @click.away="open = false" class="flex items-center gap-1 hover:text-indigo-600 focus:outline-none font-bold uppercase text-xs">
                                        Status
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                        @if(request('status')) <span class="w-2 h-2 bg-indigo-500 rounded-full"></span> @endif
                                    </button>
                                    <div x-show="open" style="display: none;" class="absolute z-50 mt-1 w-40 bg-white rounded-md shadow-lg border border-gray-200 py-1 font-normal text-sm normal-case whitespace-nowrap">
                                        <a href="{{ request()->fullUrlWithQuery(['status' => null, 'page' => null]) }}" class="block px-4 py-2 hover:bg-gray-100 {{ !request('status') ? 'font-bold text-indigo-600' : 'text-gray-700' }}">All Statuses</a>
                                        @foreach($statuses as $status)
                                            <a href="{{ request()->fullUrlWithQuery(['status' => $status, 'page' => null]) }}" class="block px-4 py-2 hover:bg-gray-100 {{ request('status') == $status ? 'font-bold text-indigo-600' : 'text-gray-700' }}">
                                                {{ $status }}
                                            </a>
                                        @endforeach
                                    </div>
                                </th>

                                <th class="border-b-2 py-2 px-4 text-left relative" x-data="{ open: false }">
                                    <button @click="open = !open" @click.away="open = false" class="flex items-center gap-1 hover:text-indigo-600 focus:outline-none font-bold uppercase text-xs">
                                        Client
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                        @if(request('client')) <span class="w-2 h-2 bg-indigo-500 rounded-full"></span> @endif
                                    </button>
                                    <div x-show="open" style="display: none;" class="absolute z-50 mt-1 w-48 bg-white rounded-md shadow-lg border border-gray-200 py-1 font-normal text-sm normal-case whitespace-nowrap">
                                        <a href="{{ request()->fullUrlWithQuery(['client' => null, 'page' => null]) }}" class="block px-4 py-2 hover:bg-gray-100 {{ !request('client') ? 'font-bold text-indigo-600' : 'text-gray-700' }}">All Clients</a>
                                        @foreach($clients as $client)
                                            <a href="{{ request()->fullUrlWithQuery(['client' => $client->id, 'page' => null]) }}" class="block px-4 py-2 hover:bg-gray-100 {{ request('client') == $client->id ? 'font-bold text-indigo-600' : 'text-gray-700' }}">
                                                {{ $client->name }}
                                            </a>
                                        @endforeach
                                    </div>
                                </th>

                                <th class="border-b-2 py-2 px-4 text-left relative" x-data="{ open: false }">
                                    <button @click="open = !open" @click.away="open = false" class="flex items-center gap-1 hover:text-indigo-600 focus:outline-none font-bold uppercase text-xs">
                                        Staff
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                        @if(request('staff')) <span class="w-2 h-2 bg-indigo-500 rounded-full"></span> @endif
                                    </button>
                                    <div x-show="open" style="display: none;" class="absolute right-0 z-50 mt-1 w-48 bg-white rounded-md shadow-lg border border-gray-200 py-1 font-normal text-sm normal-case whitespace-nowrap">
                                        <a href="{{ request()->fullUrlWithQuery(['staff' => null, 'page' => null]) }}" class="block px-4 py-2 hover:bg-gray-100 {{ !request('staff') ? 'font-bold text-indigo-600' : 'text-gray-700' }}">All Staff</a>
                                        <a href="{{ request()->fullUrlWithQuery(['staff' => 'unassigned', 'page' => null]) }}" class="block px-4 py-2 hover:bg-gray-100 {{ request('staff') == 'unassigned' ? 'font-bold text-indigo-600' : 'text-gray-700' }}">Unassigned</a>
                                        @foreach($staffMembers as $staff)
                                            <a href="{{ request()->fullUrlWithQuery(['staff' => $staff->id, 'page' => null]) }}" class="block px-4 py-2 hover:bg-gray-100 {{ request('staff') == $staff->id ? 'font-bold text-indigo-600' : 'text-gray-700' }}">
                                                {{ $staff->name }}
                                            </a>
                                        @endforeach
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tickets as $ticket)
                                <tr class="hover:bg-gray-100">
                                    <td class="border-b py-2 px-4">{{ $ticket->id }}</td>
                                    <td class="border-b py-2 px-4 font-bold">
                                        <a href="{{ route('tickets.show', $ticket) }}" class="text-blue-600 hover:text-blue-800 hover:underline">
                                            {{ $ticket->title }}
                                        </a>
                                    </td>
                                    <td class="border-b py-2 px-4">{{ $ticket->category->name }}</td>
                                    <td class="border-b py-2 px-4">{{ $ticket->priority->name }}</td>
                                    
                                    <td class="border-b py-2 px-4">
                                        @if(auth()->user()->role === 'admin' || auth()->user()->role === 'staff')
                                            <form action="{{ route('tickets.updateStatus', $ticket) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <select name="status" onchange="this.form.submit()" class="text-sm border-gray-300 rounded-md py-1">
                                                    <option value="New" {{ $ticket->status == 'New' ? 'selected' : '' }}>New</option>
                                                    <option value="In Progress" {{ $ticket->status == 'In Progress' ? 'selected' : '' }}>In Progress</option>
                                                    <option value="Resolved" {{ $ticket->status == 'Resolved' ? 'selected' : '' }}>Resolved</option>
                                                    <option value="Rejected" {{ $ticket->status == 'Rejected' ? 'selected' : '' }}>Rejected</option>
                                                    <option value="Closed" {{ $ticket->status == 'Closed' ? 'selected' : '' }}>Closed</option>
                                                </select>
                                            </form>
                                        @else
                                            <span class="text-sm font-semibold text-gray-700">{{ $ticket->status }}</span>
                                        @endif
                                    </td>
                                    
                                    <td class="border-b py-2 px-4">{{ $ticket->client?->name ?? 'Deleted user' }}</td>
                                    
                                    <td class="border-b py-2 px-4 text-sm">
                                        {{ $ticket->staff?->name ?? 'Not assigned / Staff deleted' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="border-b py-4 px-4 text-center text-gray-500">
                                        No tickets found matching your filters.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    
                    <div class="mt-4">
                        {{ $tickets->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
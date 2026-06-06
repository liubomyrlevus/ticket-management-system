<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Ticket #{{ $ticket->id }} Details
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg flex flex-col md:flex-row">
                
                <div class="p-6 text-gray-900 w-full md:w-[55%] border-r border-gray-200">
                    <h3 class="text-2xl font-bold mb-2">{{ $ticket->title }}</h3>
                    
                    <div class="bg-gray-50 p-4 rounded-md border border-gray-200 min-h-[150px] mb-6">
                        {{ $ticket->description }}
                    </div>

                    <h4 class="text-lg font-bold border-b pb-2 mb-4">Comments</h4>
                    
                    <div class="space-y-4 mb-6">
                        @forelse($ticket->comments as $comment)
                            <div class="bg-gray-50 p-4 rounded-md border border-gray-200">
                                <div class="flex justify-between text-xs text-gray-500 mb-2 border-b pb-1">
                                    <span class="font-bold text-gray-700">{{ $comment->user->name }} ({{ ucfirst($comment->user->role) }})</span>
                                    <span>{{ $comment->created_at->format('M d, Y H:i') }}</span>
                                </div>
                                <p class="text-sm whitespace-pre-wrap">{{ $comment->content }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 italic">No comments yet.</p>
                        @endforelse
                    </div>

                    <form action="{{ route('comments.store', $ticket) }}" method="POST">
                        @csrf
                        <textarea name="content" required class="w-full rounded-md border-gray-300 text-sm mb-2 focus:border-blue-500 focus:ring-blue-500" rows="3" placeholder="Write a comment..."></textarea>
                        <button type="submit" class="mt-3 inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-600 active:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm">
                            Post Comment
                        </button>
                    </form>
                </div>

                <div class="p-6 bg-gray-50 w-full md:w-[45%] flex flex-col">
                    <h4 class="font-bold text-lg mb-4 border-b pb-2">Ticket Control</h4>

                    @if(auth()->user()->role === 'admin')
                        <div class="mb-6 space-y-4 p-4 bg-white rounded-md border border-gray-200 shadow-sm">
                            <h5 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Admin Override</h5>
                            <form action="{{ route('tickets.update', $ticket) }}" method="POST">
                                @csrf @method('PUT')
                                <label class="block text-sm font-bold text-gray-700 mb-1">Status:</label>
                                <select name="status" onchange="this.form.submit();" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm">
                                    <option value="New" {{ $ticket->status == 'New' ? 'selected' : '' }}>New</option>
                                    <option value="In Progress" {{ $ticket->status == 'In Progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="Resolved" {{ $ticket->status == 'Resolved' ? 'selected' : '' }}>Resolved</option>
                                    <option value="Rejected" {{ $ticket->status == 'Rejected' ? 'selected' : '' }}>Rejected</option>
                                    <option value="Closed" {{ $ticket->status == 'Closed' ? 'selected' : '' }}>Closed</option>
                                </select>
                            </form>
                            
                            <form action="{{ route('tickets.update', $ticket) }}" method="POST">
                                @csrf @method('PUT')
                                <label class="block text-sm font-bold text-gray-700 mb-1">Assignee:</label>
                                <select name="staff_id" onchange="this.form.submit();" class="block w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 shadow-sm">
                                    <option value="">-- Unassigned --</option>
                                    @foreach($staffMembers as $staff)
                                        <option value="{{ $staff->id }}" {{ $ticket->staff_id == $staff->id ? 'selected' : '' }}>{{ $staff->name }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </div>
                    @endif

                    @if(auth()->user()->role !== 'admin')
                        <div class="mb-6 flex justify-between items-center bg-white p-3 rounded-md border border-gray-200 shadow-sm">
                            <div>
                                <span class="block text-xs text-gray-500 uppercase tracking-wider font-bold mb-1">Status</span>
                                @php
                                    $statusColors = [
                                        'New' => 'bg-green-100 text-green-800',
                                        'In Progress' => 'bg-blue-100 text-blue-800',
                                        'Resolved' => 'bg-purple-100 text-purple-800',
                                        'Rejected' => 'bg-red-100 text-red-800',
                                        'Closed' => 'bg-gray-200 text-gray-800',
                                    ];
                                    $colorClass = $statusColors[$ticket->status] ?? 'bg-gray-100 text-gray-800';
                                @endphp
                                <span class="{{ $colorClass }} px-2.5 py-0.5 rounded text-sm font-bold shadow-sm">{{ $ticket->status }}</span>
                            </div>
                            <div class="text-right">
                                <span class="block text-xs text-gray-500 uppercase tracking-wider font-bold mb-1">Assignee</span>
                                <span class="text-sm font-bold {{ $ticket->staff_id === auth()->id() ? 'text-indigo-600' : 'text-gray-700' }}">
                                    {{ $ticket->staff ? $ticket->staff->name : 'Unassigned' }}
                                </span>
                            </div>
                        </div>
                    @endif

                    @if(auth()->user()->role === 'client' && in_array($ticket->status, ['Resolved', 'Rejected']))
                        <div class="mt-2 p-4 bg-yellow-50 border border-yellow-200 rounded-md mb-6 shadow-sm">
                            <p class="text-sm text-yellow-800 font-bold mb-3">
                                The ticket is currently {{ $ticket->status }}. Please provide your feedback and select an action:
                            </p>
                            
                            <form action="{{ route('tickets.update', $ticket) }}" method="POST" class="space-y-3">
                                @csrf @method('PUT')
                                <textarea name="client_comment" required class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-yellow-500 focus:ring-yellow-500" rows="3" placeholder="Explain why you are closing or reopening this ticket..."></textarea>
                                
                                <div class="flex gap-2">
                                    <button type="submit" name="client_action" value="In Progress" class="flex-1 bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-2 rounded transition shadow-sm text-sm">
                                        Reopen
                                    </button>
                                    <button type="submit" name="client_action" value="Closed" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-2 rounded transition shadow-sm text-sm">
                                        Accept & Close
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif

                    @if(auth()->user()->role !== 'client')
                        @if($ticket->staff_id === auth()->id())
                            <div class="mb-4">
                                <h5 class="font-bold text-gray-800 mb-2">My Quick Actions</h5>
                                <p class="text-xs text-gray-500 mb-3">Add a required comment and select an action to process this ticket.</p>
                                
                                <form action="{{ route('tickets.update', $ticket) }}" method="POST" class="space-y-3">
                                    @csrf @method('PUT')
                                    <textarea name="action_comment" required class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" rows="3" placeholder="Explain your decision..."></textarea>
                                    
                                    <div class="flex flex-col gap-2">
                                        <div class="flex gap-2">
                                            <button type="submit" name="quick_action" value="release" class="flex-1 bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-2 rounded transition shadow-sm text-sm">
                                                Release
                                            </button>
                                            <button type="submit" name="quick_action" value="Rejected" class="flex-1 bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-2 rounded transition shadow-sm text-sm">
                                                Reject
                                            </button>
                                        </div>
                                        <button type="submit" name="quick_action" value="Resolved" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded transition shadow-sm text-sm">
                                            Resolve Ticket
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @elseif(is_null($ticket->staff_id))
                            <div class="mb-4">
                                <form action="{{ route('tickets.update', $ticket) }}" method="POST">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="staff_id" value="{{ auth()->id() }}">
                                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded transition shadow-sm">
                                        Claim Ticket
                                    </button>
                                </form>
                            </div>
                        @endif
                    @endif

                    <div class="flex-grow"></div>

                    <div class="mt-6 pt-4 border-t border-gray-200 flex justify-center">
                        <a href="{{ route('tickets.index') }}" 
                        class="inline-flex items-center px-6 py-2 bg-gray-800 border border-transparent rounded-md text-white hover:bg-blue-600 focus:bg-blue-600 active:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors ease-in-out duration-200 shadow-sm"
                        title="Повернутися до списку">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.7" d="M7 11L3 7l4-4"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.7" d="M12 21 C 24 20, 23 7, 3 7"></path>
                            </svg>
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
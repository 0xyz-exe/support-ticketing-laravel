<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">{{ auth()->user()->is_support_agent ? 'Support queue' : 'My support tickets' }}</h2></x-slot>
    <main class="py-8 max-w-5xl mx-auto space-y-6">
        @if(!auth()->user()->is_support_agent)
            <section class="bg-white p-6 shadow rounded"><h3 class="font-bold text-lg">Open a ticket</h3>
                <form method="post" action="{{ route('tickets.store') }}" class="space-y-3 mt-3">@csrf
                    <label class="block">Subject<input name="subject" maxlength="160" required class="block w-full rounded border"></label>
                    <label class="block">Description<textarea name="description" maxlength="10000" required rows="4" class="block w-full rounded border"></textarea></label>
                    <label class="block">Priority<select name="priority" class="block rounded border"><option value="normal">Normal</option><option value="low">Low</option><option value="high">High</option><option value="urgent">Urgent</option></select></label>
                    <button class="px-4 py-2 bg-indigo-600 text-white rounded">Submit ticket</button>
                </form>
            </section>
        @endif
        <section class="bg-white p-6 shadow rounded">
            <form method="get" class="flex gap-2 mb-4"><label for="q" class="sr-only">Search subject</label><input id="q" name="q" value="{{ request('q') }}" placeholder="Search subject" class="rounded border"><select name="status" class="rounded border"><option value="">All statuses</option>@foreach(['open','in_progress','waiting','resolved','closed'] as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ str_replace('_',' ',$status) }}</option>@endforeach</select><button class="rounded bg-gray-800 text-white px-3">Filter</button></form>
            <div class="overflow-x-auto"><table class="w-full text-left"><thead><tr><th>Ticket</th>@if(auth()->user()->is_support_agent)<th>Requester</th>@endif<th>Status</th><th>Priority</th><th>Updated</th></tr></thead><tbody>
                @forelse($tickets as $ticket)<tr class="border-t"><td class="py-3"><a class="text-indigo-700 underline" href="{{ route('tickets.show',$ticket) }}">#{{ $ticket->id }} · {{ $ticket->subject }}</a><small class="block">{{ $ticket->comments_count }} replies</small></td>@if(auth()->user()->is_support_agent)<td>{{ $ticket->requester->name }}</td>@endif<td>{{ str_replace('_',' ',$ticket->status) }}</td><td>{{ $ticket->priority }}</td><td>{{ $ticket->updated_at->diffForHumans() }}</td></tr>
                @empty<tr><td colspan="5" class="py-4">No tickets found.</td></tr>@endforelse
            </tbody></table></div>{{ $tickets->links() }}
        </section>
    </main>
</x-app-layout>

<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Ticket #{{ $ticket->id }} · {{ $ticket->subject }}</h2></x-slot>
    <main class="py-8 max-w-4xl mx-auto space-y-6">
        @if(session('status'))<p role="status" class="p-3 bg-green-100">{{ session('status') }}</p>@endif
        <section class="bg-white p-6 shadow rounded"><p>From {{ $ticket->requester->name }} · {{ $ticket->created_at->format('M j, Y H:i') }}</p><p class="mt-4 whitespace-pre-wrap">{{ $ticket->description }}</p><p class="mt-3">Status: <b>{{ str_replace('_',' ',$ticket->status) }}</b> · Priority: <b>{{ $ticket->priority }}</b> · Assignee: {{ $ticket->assignee?->name ?? 'Unassigned' }}</p>
            @if(auth()->user()->is_support_agent)
                <form method="post" action="{{ route('tickets.update',$ticket) }}" class="mt-4 flex flex-wrap gap-3">@csrf @method('PATCH')
                    <label>Status<select name="status" class="ml-2 rounded border">@foreach(['open','in_progress','waiting','resolved','closed'] as $status)<option value="{{ $status }}" @selected($ticket->status===$status)>{{ str_replace('_',' ',$status) }}</option>@endforeach</select></label>
                    <label>Priority<select name="priority" class="ml-2 rounded border">@foreach(['low','normal','high','urgent'] as $priority)<option @selected($ticket->priority===$priority)>{{ $priority }}</option>@endforeach</select></label>
                    <label>Assign<select name="assignee_id" class="ml-2 rounded border"><option value="">Unassigned</option>@foreach($agents as $agent)<option value="{{ $agent->id }}" @selected($ticket->assignee_id===$agent->id)>{{ $agent->name }}</option>@endforeach</select></label>
                    <button class="px-3 py-2 bg-indigo-600 text-white rounded">Update</button>
                </form>
            @endif
        </section>
        <section class="bg-white p-6 shadow rounded"><h3 class="font-bold text-lg">Conversation</h3>
            @forelse($ticket->comments as $comment)<article class="border-t py-3"><p class="font-semibold">{{ $comment->user->name }} @if($comment->is_internal)<span class="text-amber-700">· Internal note</span>@endif</p><p class="whitespace-pre-wrap">{{ $comment->body }}</p><small>{{ $comment->created_at->format('M j, Y H:i') }}</small></article>@empty<p class="mt-3">No replies yet.</p>@endforelse
            <form method="post" action="{{ route('tickets.comments.store',$ticket) }}" class="border-t pt-4 mt-3">@csrf
                <label class="block">{{ auth()->user()->is_support_agent ? 'Reply or add note' : 'Reply' }}<textarea name="body" required maxlength="10000" rows="4" class="block w-full rounded border mt-1"></textarea></label>
                @if(auth()->user()->is_support_agent)<label class="block mt-2"><input type="checkbox" name="is_internal" value="1"> Internal note (hidden from requester)</label>@endif
                <button class="mt-3 px-4 py-2 bg-indigo-600 text-white rounded">Send reply</button>
            </form>
        </section>
    </main>
</x-app-layout>

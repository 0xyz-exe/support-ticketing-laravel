<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $tickets = Ticket::with(['requester:id,name', 'assignee:id,name'])->withCount('comments')
            ->when(!$request->user()->is_support_agent, fn ($q) => $q->where('user_id', $request->user()->id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('q'), fn ($q) => $q->where('subject', 'like', '%'.str_replace(['%', '_'], ['\\%', '\\_'], $request->query('q')).'%'))
            ->latest()->paginate(20)->withQueryString();
        return view('tickets.index', compact('tickets'));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['subject' => 'required|string|max:160', 'description' => 'required|string|max:10000', 'priority' => 'required|in:low,normal,high,urgent']);
        $ticket = $request->user()->tickets()->create($data + ['status' => 'open']);
        return redirect()->route('tickets.show', $ticket)->with('status', 'Ticket submitted.');
    }

    public function show(Request $request, Ticket $ticket)
    {
        $this->authorizeTicket($request, $ticket);
        $ticket->load(['requester:id,name', 'assignee:id,name', 'comments' => fn ($q) => $q->where(fn ($query) => $query->where('is_internal', false)->orWhere('user_id', $request->user()->id))->with('user:id,name')->oldest()]);
        return view('tickets.show', ['ticket' => $ticket, 'agents' => $request->user()->is_support_agent ? User::where('is_support_agent', true)->orderBy('name')->get(['id', 'name']) : collect()]);
    }

    public function comment(Request $request, Ticket $ticket)
    {
        $this->authorizeTicket($request, $ticket);
        $data = $request->validate(['body' => 'required|string|max:10000', 'is_internal' => 'sometimes|boolean']);
        abort_if(($data['is_internal'] ?? false) && !$request->user()->is_support_agent, 403);
        $ticket->comments()->create(['user_id' => $request->user()->id, 'body' => $data['body'], 'is_internal' => $request->user()->is_support_agent && ($data['is_internal'] ?? false)]);
        $ticket->touch();
        return back()->with('status', 'Reply added.');
    }

    public function update(Request $request, Ticket $ticket)
    {
        abort_unless($request->user()->is_support_agent, 403);
        $data = $request->validate(['status' => 'required|in:open,in_progress,waiting,resolved,closed', 'priority' => 'required|in:low,normal,high,urgent', 'assignee_id' => 'nullable|integer|exists:users,id']);
        if ($data['assignee_id']) abort_unless(User::whereKey($data['assignee_id'])->where('is_support_agent', true)->exists(), 422);
        $ticket->update($data);
        return back()->with('status', 'Ticket updated.');
    }

    private function authorizeTicket(Request $request, Ticket $ticket): void
    {
        abort_unless($request->user()->is_support_agent || $ticket->user_id === $request->user()->id, 404);
    }
}

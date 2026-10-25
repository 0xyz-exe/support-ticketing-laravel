<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_requester_can_create_and_only_view_own_tickets(): void
    {
        $requester = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($requester)->post(route('tickets.store'), ['subject' => 'Cannot sign in', 'description' => 'Login fails', 'priority' => 'high'])->assertRedirect();
        $ticket = Ticket::firstOrFail();
        $this->assertSame('open', $ticket->status);
        $this->actingAs($requester)->get(route('tickets.show', $ticket))->assertOk()->assertSee('Login fails');
        $this->actingAs($other)->get(route('tickets.show', $ticket))->assertNotFound();
        $this->actingAs($other)->get(route('tickets.index'))->assertOk()->assertDontSee('Cannot sign in');
    }

    public function test_agent_can_assign_manage_and_add_private_note_hidden_from_requester(): void
    {
        $requester = User::factory()->create();
        $agent = User::factory()->create();
        $agent->forceFill(['is_support_agent' => true])->save();
        $ticket = Ticket::create(['user_id' => $requester->id, 'subject' => 'Issue', 'description' => 'Details', 'priority' => 'normal', 'status' => 'open']);
        $this->actingAs($agent)->patch(route('tickets.update', $ticket), ['status' => 'in_progress', 'priority' => 'urgent', 'assignee_id' => $agent->id])->assertRedirect();
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'status' => 'in_progress', 'assignee_id' => $agent->id]);
        $this->actingAs($agent)->post(route('tickets.comments.store', $ticket), ['body' => 'Investigate logs', 'is_internal' => 1])->assertRedirect();
        $this->actingAs($requester)->get(route('tickets.show', $ticket->fresh()))->assertOk()->assertDontSee('Investigate logs');
        $this->actingAs($agent)->get(route('tickets.show', $ticket->fresh()))->assertOk()->assertSee('Investigate logs');
    }

    public function test_requester_cannot_post_internal_notes_or_update_ticket(): void
    {
        $requester = User::factory()->create();
        $ticket = Ticket::create(['user_id' => $requester->id, 'subject' => 'Issue', 'description' => 'Details', 'priority' => 'normal', 'status' => 'open']);
        $this->actingAs($requester)->post(route('tickets.comments.store', $ticket), ['body' => 'Private', 'is_internal' => 1])->assertForbidden();
        $this->actingAs($requester)->patch(route('tickets.update', $ticket), ['status' => 'closed', 'priority' => 'urgent', 'assignee_id' => null])->assertForbidden();
        $this->assertDatabaseCount('ticket_comments', 0);
    }
}

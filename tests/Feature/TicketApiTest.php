<?php

namespace Tests\Feature;

use App\Models\ServiceLine;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_dashboard_summary_returns_counts(): void
    {
        $response = $this->getJson('/api/v1/dashboard');

        $response->assertOk()
            ->assertJsonStructure(['open_urgent', 'by_status', 'by_type', 'totals']);
    }

    public function test_can_create_ticket_via_api(): void
    {
        $accountId = 1;

        $response = $this->postJson('/api/v1/tickets', [
            'account_id' => $accountId,
            'type' => 'fault',
            'subject' => 'Test SIP registration flap',
            'priority' => 'high',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.subject', 'Test SIP registration flap')
            ->assertJsonPath('data.status', 'open');

        $ticketId = $response->json('data.id');
        $this->getJson("/api/v1/tickets/{$ticketId}")
            ->assertOk()
            ->assertJsonPath('data.status_histories.0.to_status', 'open');
    }

    public function test_ticket_list_rejects_unknown_status_filter(): void
    {
        $this->getJson('/api/v1/tickets?status=not_a_status')
            ->assertStatus(422);
    }

    public function test_ticket_list_rejects_unknown_sort_column(): void
    {
        $this->getJson('/api/v1/tickets?sort=not_a_column')
            ->assertStatus(422);
    }

    public function test_ticket_list_sorts_by_account_while_filtered(): void
    {
        $rows = $this->getJson('/api/v1/tickets?sort=account&direction=asc&status=open&per_page=100')
            ->assertOk()
            ->json('data.data');

        $this->assertNotEmpty($rows);
        foreach ($rows as $row) {
            $this->assertSame('open', $row['status']);
        }

        $names = collect($rows)->pluck('account.name')->all();
        $expected = $names;
        sort($expected, SORT_STRING);
        $this->assertSame($expected, $names);
    }

    public function test_ticket_search_treats_percent_as_literal(): void
    {
        $all = $this->getJson('/api/v1/tickets?per_page=10')->json('data.total');
        $literal = $this->getJson('/api/v1/tickets?'.http_build_query([
            'q' => '%',
            'per_page' => 10,
        ]))->assertOk()->json('data.total');

        $this->assertGreaterThan(0, $all);
        $this->assertLessThan($all, $literal);
    }

    public function test_ticket_list_sorts_by_reference(): void
    {
        $refs = $this->getJson('/api/v1/tickets?sort=reference&direction=asc&per_page=100')
            ->assertOk()
            ->json('data.data');

        $this->assertNotEmpty($refs);
        $sorted = collect($refs)->pluck('reference')->all();
        $expected = $sorted;
        sort($expected, SORT_STRING);
        $this->assertSame($expected, $sorted);
    }

    public function test_ticket_list_accepts_empty_legacy_filter_params(): void
    {
        $this->getJson('/api/v1/tickets?status=&type=&q=&per_page=100&page=1')
            ->assertOk()
            ->assertJsonStructure(['data' => ['data', 'current_page', 'last_page', 'total']]);
    }

    public function test_create_ticket_rejects_service_line_from_another_account(): void
    {
        $ownedLine = ServiceLine::query()->firstOrFail();
        $otherLine = ServiceLine::query()
            ->where('account_id', '!=', $ownedLine->account_id)
            ->firstOrFail();

        $this->postJson('/api/v1/tickets', [
            'account_id' => $ownedLine->account_id,
            'service_line_id' => $otherLine->id,
            'type' => 'fault',
            'subject' => 'Cross-account line should fail validation',
            'priority' => 'normal',
        ])->assertStatus(422);
    }

    public function test_status_change_logs_history(): void
    {
        $ticket = Ticket::query()->where('status', 'open')->first()
            ?? Ticket::query()->first();
        $this->assertNotNull($ticket);

        $this->assertNotSame('waiting', $ticket->status);

        $this->patchJson("/api/v1/tickets/{$ticket->id}", [
            'status' => 'waiting',
            'status_note' => 'Awaiting customer callback window.',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'waiting');

        $this->getJson("/api/v1/tickets/{$ticket->id}")
            ->assertOk()
            ->assertJsonPath('data.status_histories.0.to_status', 'waiting')
            ->assertJsonPath('data.status_histories.0.note', 'Awaiting customer callback window.');

        $this->patchJson("/api/v1/tickets/{$ticket->id}", ['status' => 'resolved'])
            ->assertStatus(422);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Quote;
use Database\Seeders\UcDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CorporateApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UcDemoSeeder::class);
    }

    public function test_dashboard_includes_corporate_totals(): void
    {
        $response = $this->getJson('/api/v1/dashboard');

        $response->assertOk()
            ->assertJsonStructure([
                'quotes_by_status',
                'provisioning_by_status',
                'totals' => ['quotes', 'provisioning_orders'],
            ]);

        $this->assertGreaterThan(0, $response->json('totals.quotes'));
    }

    public function test_quotes_index_and_show(): void
    {
        $quote = Quote::query()->firstOrFail();

        $this->getJson('/api/v1/quotes')
            ->assertOk()
            ->assertJsonStructure(['data' => ['data', 'total']]);

        $this->getJson("/api/v1/quotes/{$quote->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $quote->id)
            ->assertJsonStructure(['data' => ['items', 'account']]);
    }

    public function test_provisioning_orders_index(): void
    {
        $this->getJson('/api/v1/provisioning-orders')
            ->assertOk()
            ->assertJsonStructure(['data' => ['data', 'total']]);
    }

    public function test_quotes_sort_by_account(): void
    {
        $this->getJson('/api/v1/quotes?sort=drop_table')->assertStatus(422);

        $rows = $this->getJson('/api/v1/quotes?sort=account&direction=asc&per_page=100')
            ->assertOk()
            ->json('data.data');

        $this->assertNotEmpty($rows);
        $this->assertArrayHasKey('items_count', $rows[0]);

        $names = collect($rows)->pluck('account.name')->all();
        $expected = $names;
        sort($expected, SORT_STRING);
        $this->assertSame($expected, $names);
    }

    public function test_provisioning_quote_sort_stays_filtered(): void
    {
        $rows = $this->getJson('/api/v1/provisioning-orders?'.http_build_query([
            'sort' => 'quote',
            'direction' => 'asc',
            'status' => 'submitted',
            'q' => 'a',
            'per_page' => 100,
        ]))->assertOk()->json('data.data');

        foreach ($rows as $row) {
            $this->assertSame('submitted', $row['status']);
        }
    }
}

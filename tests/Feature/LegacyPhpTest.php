<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyPhpTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_summary_php_returns_json_queue(): void
    {
        $this->seed();

        $response = $this->get('/legacy/ticket_summary.php');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/json; charset=utf-8');
        $response->assertJsonStructure([
            'generated_by',
            'open_queue',
            'count',
            'total',
            'current_page',
            'last_page',
            'per_page',
        ]);
    }

    public function test_legacy_admin_html_is_served(): void
    {
        $response = $this->get('/legacy/admin.html');

        $response->assertOk();
        $response->assertSee('Staff ticket queue (legacy jQuery)', false);
        $response->assertSee('legacy-table-sort.js?v=14', false);
        $response->assertSee('legacy-sort-glyph', false);
    }

    public function test_legacy_table_sort_js_is_served_with_short_cache(): void
    {
        $response = $this->get('/legacy/legacy-table-sort.js');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/javascript; charset=utf-8');
        $this->assertStringContainsString('must-revalidate', (string) $response->headers->get('cache-control'));
        $response->assertSee('bindServerSort', false);
        $response->assertSee('legacy-sort-glyph', false);
    }
}

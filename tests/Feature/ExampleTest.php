<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_portfolio_page_is_available_for_hr_review(): void
    {
        $response = $this->get('/portfolio');

        $response->assertStatus(200);
        $response->assertSee('HR review guide', false);
        $response->assertSee('Five-minute walkthrough', false);
        $response->assertSee(config('demo.portfolio.author'), false);
        $response->assertSee('legacy-scroll.js', false);
    }
}

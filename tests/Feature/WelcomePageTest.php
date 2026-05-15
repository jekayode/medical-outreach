<?php

namespace Tests\Feature;

use Tests\TestCase;

class WelcomePageTest extends TestCase
{
    public function test_home_page_shows_outreach_landing_content(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('FREE', false);
        $response->assertSee('Medical Outreach', false);
        $response->assertSee('16', false);
        $response->assertSee('May, 2026', false);
        $response->assertSee('Synlab', false);
        $response->assertSee('10:00 AM', false);
        $response->assertSee('outreach-flyer-2026.png', false);
    }
}

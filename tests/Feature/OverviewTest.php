<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OverviewTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test /tools/overview route renders Studio Overview view inside application shell.
     */
    public function test_studio_overview_page_is_accessible(): void
    {
        $response = $this->get('/tools/overview');

        $response->assertStatus(200);
        $response->assertSee('AI CREATIVE STUDIO');
        $response->assertSee('Create Beyond');
        $response->assertSee('Imagination');
        $response->assertSee('Text-to-Image Generator');
        $response->assertSee('Text-to-Video Generator');
        $response->assertSee('CINEMATIC MOTION CORE');
        $response->assertSee('Wan 2.2 Cinematic');
        $response->assertSee('Hunyuan-Video');
        $response->assertSee('Studio Overview');
    }
}

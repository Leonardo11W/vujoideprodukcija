<?php

namespace Modules\Frontend\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FrontendTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test Frontend.
     *
     * @return void
     */
    public function test_backend_frontends_list_page()
    {
        $this->signInAsAdmin();

        $response = $this->get('app/frontends');

        $response->assertStatus(200);
    }
}

<?php

namespace Tests\Feature;

use Tests\AbstractTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExampleTest extends AbstractTestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testBasicTest()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }
}

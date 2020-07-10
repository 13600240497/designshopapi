<?php
namespace Tests\Feature\Test;

use Tests\AbstractTestCase;
use App\Http\Controllers\Test\RmsController;

class RmsControllerTest extends AbstractTestCase
{
    /**
     * 测试rms报警
     */
    public function testBasicTest()
    {
        $response = $this->get('/test/rms/trigger');
        $response->assertStatus(200);
    }
}

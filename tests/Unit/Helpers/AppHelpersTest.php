<?php
namespace Tests\Unit\Helpers;

use Tests\AbstractTestCase;
use App\Helpers\AppHelpers;

class AppHelpersTest extends AbstractTestCase
{
    public function testGetAsyncApiConfig()
    {
        $pcConfig = AppHelpers::getAsyncApiConfig('zf-pc');
        $this->assertTrue(isset($pcConfig['goods_getDetail']));
        $this->assertContains('//www.', $pcConfig['goods_getDetail']['url']);

        $mConfig = AppHelpers::getAsyncApiConfig('zf-wap');
        $this->assertTrue(isset($mConfig['goods_getDetail']));
        $this->assertContains('//m.', $mConfig['goods_getDetail']['url']);
    }
}

<?php
namespace Tests\Unit\Other;

use App\Combines\NativePageCombines;
use Tests\AbstractTestCase;
use App\Helpers\ContainerHelpers;

class RedisCacheTest extends AbstractTestCase
{
    public function testDel()
    {
        $redis = ContainerHelpers::getPredisResolveCache();

//        $key = 'geshopApi:test:unit:1111';
//        $result = $redis->set($key, 'hello word!');
//        $this->assertTrue(!empty($result));
//        $redis->del([$key]);
//        $this->assertEquals(0, $redis->exists($key));

        $keys = [
            'test::www.geshop-api.com.tianhaishen.dev.local.com::geshop::native::app::json::zf-app',
            'test::www.geshop-api.com.tianhaishen.dev.local.com::geshop::native::wap::json::zf-wap'
        ];
        $numbers = $redis->del($keys);
        $this->assertTrue($numbers > 0);

        for ($i = 0; $i < 1000; $i++) {
            $redis->publish('activity_page_publish', json_encode(['site_code' => 'zf-pc', 'page_id' => 1 + $i, 'lang' => 'en']));
        }
        $this->assertIsString('ok');
    }
}

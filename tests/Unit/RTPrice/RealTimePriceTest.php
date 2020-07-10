<?php
namespace Tests\Unit\RTPrice;

use App\Services\RTPrice\RealTimePrice;
use Tests\AbstractTestCase;

/**
 * 实时价格测试
 *
 * @author tianhaishen
 */
class RealTimePriceTest extends AbstractTestCase
{

    /**
     * 测试S3 json数据文件
     */
    public function testJsonFile()
    {
        $realTimePrice = new RealTimePrice('zf-wap', 'en', 'ZF');
        $jsonData = $realTimePrice->getUiAsyncData(39969);
        print_r($jsonData);
        $this->assertNotEmpty($jsonData);
    }
}

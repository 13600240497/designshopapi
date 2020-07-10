<?php
namespace Tests\Unit\NativePage;

use App\Helpers\AppHelpers;
use Tests\AbstractTestCase;
use App\Services\NativePage\PageDataFallback;

class PageDataFallbackTest extends AbstractTestCase
{
    /**
     * 测试价组件异步数据兜底
     */
    public function testGetAppAsyncInfo()
    {
        $uiDataList = [];
        $jsonBody = file_get_contents('https://geshop.s3.amazonaws.com/publish/m.zaful.com/en/app/app-component-data-68760.json');
        if (!empty($jsonBody)) {
            $data = AppHelpers::jsonDecode($jsonBody, true);
            $uiDataList = $data['result'];
        }
        $this->assertNotEmpty($uiDataList);

        $mockFallback = $this->getMockBuilder(PageDataFallback::class)
            ->setConstructorArgs(['zf-app', 68760, 'ZF', 'en'])
            ->setMethods(['getAppData'])
            ->getMock();

        $mockFallback->expects($this->once())
            ->method('getAppData')
            ->willReturn($uiDataList);

        $asyncInfo = $mockFallback->getAppAsyncInfo();
        echo AppHelpers::jsonEncode($asyncInfo);
        $this->assertNotEmpty($asyncInfo);
    }
}

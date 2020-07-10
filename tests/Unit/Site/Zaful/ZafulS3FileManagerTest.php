<?php
namespace Tests\Unit\Site\Zaful;

use App\Helpers\S3Helpers;
use App\Helpers\AppHelpers;
use App\Helpers\BeanHelpers;
use App\Services\Site\zaful\ZafulS3FileManager;
use Tests\AbstractTestCase;

/**
 * zaful S3文件管理测试
 *
 * @author tianhaishen
 */
class ZafulS3FileManagerTest extends AbstractTestCase
{
    /**
     * 测试M端S3 json数据文件
     */
    public function testWapJsonFile()
    {
        $s3FileManager = BeanHelpers::getS3FileManager('zf-wap', 'en', 'ZF');

        // 非原生页面自动刷新组件数据
        $filename = S3Helpers::getUiAutoRefreshJsonFile(39969);
        $s3Url = $s3FileManager->getJsonDataFileUrl($filename);
        $jsonData = AppHelpers::jsonDecode(file_get_contents($s3Url), true);
        $this->assertEquals('async-data-39969.json', $filename);
        $this->assertNotEmpty($jsonData);

        // 原生页面组件数据
        $filename = S3Helpers::getNativePageUiDataJsonFile(40062);
        $s3Url = $s3FileManager->getJsonDataFileUrl($filename);
        $jsonData = AppHelpers::jsonDecode(file_get_contents($s3Url), true);
        $this->assertEquals('api-async-data-40062.json', $filename);
        $this->assertNotEmpty($jsonData);

        // 原生页面兜底数据
        $filename = S3Helpers::getNativePageWapFallbackDataJsonFile(40062);
        $s3Url = $s3FileManager->getJsonDataFileUrl($filename);
        $jsonData = AppHelpers::jsonDecode(file_get_contents($s3Url), true);
        $this->assertEquals('wap-component-data-40062.json', $filename);
        $this->assertNotEmpty($jsonData);
    }

    /**
     * 测试APP端S3 json数据文件
     */
    public function testAppJsonFile()
    {
        $s3FileManager = BeanHelpers::getS3FileManager('zf-app', 'en', 'ZF');

        // 非原生页面自动刷新组件数据
        $filename = S3Helpers::getUiAutoRefreshJsonFile(40711);
        $s3Url = $s3FileManager->getJsonDataFileUrl($filename);
        $jsonData = AppHelpers::jsonDecode(file_get_contents($s3Url), true);
        $this->assertEquals('async-data-40711.json', $filename);
        $this->assertNotEmpty($jsonData);

        // 原生页面组件数据
        $filename = S3Helpers::getNativePageUiDataJsonFile(40618);
        $s3Url = $s3FileManager->getJsonDataFileUrl($filename);
        $jsonData = AppHelpers::jsonDecode(file_get_contents($s3Url), true);
        $this->assertEquals('api-async-data-40618.json', $filename);
        $this->assertNotEmpty($jsonData);

        // 原生页面兜底数据
        $filename = S3Helpers::getNativePageAppFallbackDataJsonFile(40618);
        $s3Url = $s3FileManager->getJsonDataFileUrl($filename);
        $jsonData = AppHelpers::jsonDecode(file_get_contents($s3Url), true);
        $this->assertEquals('app-component-data-40618.json', $filename);
        $this->assertNotEmpty($jsonData);
    }
}

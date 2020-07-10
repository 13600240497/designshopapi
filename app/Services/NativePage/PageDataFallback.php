<?php
namespace App\Services\NativePage;

use GuzzleHttp\Exception\RequestException;
use App\Helpers\BeanHelpers;
use App\Helpers\S3Helpers;
use App\Base\KeyConstants;
use App\Helpers\SiteHelpers;
use App\Helpers\AppHelpers;
use App\Services\Site\AbstractS3FileManager;

/**
 * 页面兜底数据
 *
 * @author TianHaisen
 */
class PageDataFallback
{
    /** @var string 网站简码，如: zf-wap */
    private $siteCode;

    /** @var int 页面ID */
    private $pageId;

    /** @var string 国家站点编码，如: ZF */
    private $pipeline;

    /** @var string 语言简码，如: en/fr */
    private $lang;

    /** @var string 兜底数据URL */
    private $fallbackDataUrl;

    /**
     * 构造函数
     *
     * @param string $siteCode 网站简码
     * @param int $pageId 页面ID
     * @param string $pipeline 国家站编码
     * @param string $lang 语言简码
     */
    public function __construct(string $siteCode, int $pageId, string $pipeline, string $lang)
    {
        $this->siteCode = $siteCode;
        $this->pageId = $pageId;
        $this->pipeline = $pipeline;
        $this->lang = $lang;
    }

    /**
     * 获取兜底数据URL
     *
     * @return string
     */
    public function getFallbackDataUrl()
    {
        return empty($this->fallbackDataUrl) ? '' : $this->fallbackDataUrl;
    }

    /**
     * M端兜底数据
     *
     * @return array
     */
    public function getWapData()
    {
        $filename = S3Helpers::getNativePageWapFallbackDataJsonFile($this->pageId);
        $jsonBody = $this->getPageFallbackJson($filename);
        if (!empty($jsonBody)) {
            $data = AppHelpers::jsonDecode($jsonBody, true);
            return $data['data'];
        }
        return [];
    }

    /**
     * APP端兜底数据
     *
     * @return array
     */
    public function getAppData()
    {
        $filename = S3Helpers::getNativePageAppFallbackDataJsonFile($this->pageId);
        $jsonBody = $this->getPageFallbackJson($filename);
        if (!empty($jsonBody)) {
            $data = AppHelpers::jsonDecode($jsonBody, true);
            return $data['result'];
        }
        return [];
    }

    /**
     * APP端兜底异步数据
     */
    public function getAppAsyncInfo()
    {
        $uiDataList = $this->getAppData();
        if (empty($uiDataList)) {
            return [];
        }

        $asyncInfo = [];
        foreach ($uiDataList as $uiInfo) {
            $uiId = $uiInfo['component_id'];
            $appUiId = (int)$uiInfo['component_type'];
            if ($appUiId < 105) {
                if (104 === $appUiId) {
                    $asyncInfo[$uiId] = [
                        'sort_list' => $uiInfo['component_data']['sort_list'] ?? [],
                        'category_list' => $uiInfo['component_data']['category_list'] ?? [],
                        'refine_list' => $uiInfo['component_data']['refine_list'] ?? [],
                    ];
                } else if (103 === $appUiId) {
                    $asyncInfo[$uiId] = [
                        KeyConstants::GOODS_LIST => $uiInfo['list'] ?? [],
                    ];
                    if (isset($uiInfo[KeyConstants::PAGINATION])) {
                        $asyncInfo[$uiId][KeyConstants::PAGINATION] = $uiInfo[KeyConstants::PAGINATION];
                    }
                }
            } else {
                $asyncInfo[$uiId] = $uiInfo[AppDataTransformer::UI_KEY_COMPONENT_ASYNC] ?? [];
            }
        }

        return $asyncInfo;
    }

    /**
     * 获取页面兜底数据json
     *
     * @param string $filename 文件名称
     * @return string
     */
    private function getPageFallbackJson($filename)
    {
        list($websiteCode, ) = SiteHelpers::splitSiteCode($this->siteCode);
        $s3FileManager = BeanHelpers::getS3FileManager($this->siteCode, $this->lang, $this->pipeline);
        $s3JsonUrl = $s3FileManager->getJsonDataFileUrl($filename);
        $defaultLang = config(sprintf('site.%s.pipelineDefaultLang.%s', $websiteCode, $this->pipeline));
        $jsonBody = $this->getS3FileBody($s3FileManager, $s3JsonUrl);
        if (empty($jsonBody) && ($this->lang !== $defaultLang)) {
            $s3FileManager = BeanHelpers::getS3FileManager($this->siteCode, $defaultLang, $this->pipeline);
            $s3JsonUrl = $s3FileManager->getJsonDataFileUrl($filename);
            $jsonBody = $this->getS3FileBody($s3FileManager, $s3JsonUrl);
        }

        return $jsonBody;
    }

    /**
     * 获取S3文件内容
     *
     * @param AbstractS3FileManager $s3FileManager
     * @param string $s3Url
     * @return string
     */
    private function getS3FileBody($s3FileManager, $s3Url)
    {
        try {
            return $s3FileManager->getFileBody($s3Url);
        } catch (RequestException $e) {
            return '';
        }
    }
}

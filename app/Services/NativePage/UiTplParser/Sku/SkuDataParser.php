<?php
namespace App\Services\NativePage\UiTplParser\Sku;

use App\Base\AppConstants;
use App\Services\NativePage\PageUiAsyncDataParser;
use App\Services\NativePage\UiTplParser\AbstractUiTplAsyncApiParser;
use App\Services\NativePage\UiTplParser\Sku\Provider\AbstractSkuProvider;
use App\Services\NativePage\UiTplParser\Sku\Provider\InputSkuProvider;
use App\Services\NativePage\UiTplParser\Sku\Provider\PriceSysSkuProvider;
use App\Services\NativePage\UiTplParser\Sku\Provider\SopSkuProvider;

/**
 * 组件商品信息解析器
 *
 * @author TianHaisen
 */
class SkuDataParser
{
    /** @var string API请求信息键名 - SKU规则ID */
    const KEY_API_SKU_ID = 'sku_id';

    /** @var string API请求信息键名 - 接口名称 */
    const KEY_API_NAME = 'api';

    /** @var string API请求信息键名 - 接口参数 */
    const KEY_API_PARAMS = 'params';


    /** @var string SKU规则键名 - ID */
    const KEY_SKU_RULE_ID = 'id';

    /** @var string SKU规则键名 - SKU来源类型 */
    const KEY_SKU_RULE_TYPE = 'type';

    /** @var string SKU规则键名 - 组件ID */
    const KEY_SKU_RULE_COMPONENT_ID = 'component_id';


    /** @var PageUiAsyncDataParser 页面解析器 */
    protected $pageParser;

    /** @var AbstractUiTplAsyncApiParser 组件解析器 */
    protected $uiTplParser;

    /** @var array 组件SKU配置列表 */
    protected $skuInfoList;

    /** @var array sku ID到 sku 配置信息映射引用 */
    protected $skuIdToSkuInfoRefer;

    /** @var array sku规则解析器 */
    private static $skuRuleParser = [
        AppConstants::SKU_FROM_INPUT        => InputSkuProvider::class,
        AppConstants::SKU_FROM_SOP          => SopSkuProvider::class,
        AppConstants::SKU_FROM_PRICE_SYS    => PriceSysSkuProvider::class,
    ];

    /** @var AbstractSkuProvider[] sku规则解析器实例 */
    private $skuRuleParserInstance = [];

    /**
     * 构造函数
     *
     * @param PageUiAsyncDataParser $pageParser 页面解析器
     * @param AbstractUiTplAsyncApiParser $uiTplParser 组件解析器
     * @param array $skuInfoList 组件SKU配置列表
     */
    public function __construct(PageUiAsyncDataParser $pageParser, AbstractUiTplAsyncApiParser $uiTplParser, $skuInfoList)
    {
        $this->pageParser = $pageParser;
        $this->uiTplParser = $uiTplParser;
        $this->skuInfoList = $skuInfoList;
        foreach ($this->skuInfoList as $key => & $skuInfoRefer) {
            $this->skuIdToSkuInfoRefer[$skuInfoRefer[self::KEY_SKU_RULE_ID]] = & $this->skuInfoList[$key];
        }
    }

    /**
     * 获取SKU规则解析器
     *
     * @param int $type SKU来源
     * @return AbstractSkuProvider|null
     */
    private function getSkuRuleParser($type)
    {
        if (!array_key_exists($type, $this->skuRuleParserInstance)) {
            $instance = null;
            if (isset(self::$skuRuleParser[$type])) {
                $class = self::$skuRuleParser[$type];
                try {
                    $rc = new \ReflectionClass($class);
                    $instance = $rc->newInstanceArgs([$this->pageParser, $this->uiTplParser]);
                } catch (\ReflectionException $e) {}
            }
            $this->skuRuleParserInstance[$type] = $instance;
        }

        return $this->skuRuleParserInstance[$type];
    }

    /**
     * 根据SKU数据字段生成 API 请求信息
     *
     * @return array
     */
    public function buildAsyncApiInfo()
    {
        $apiInfoList = [];
        foreach ($this->skuInfoList as $skuInfo) {
            $type = (int)$skuInfo[self::KEY_SKU_RULE_TYPE];

            // 不支持的解析类型
            if (!isset(self::$skuRuleParser[$type])) {
                continue;
            }

            // 没有获取到对应SKU规则解析器
            $skuRuleParser = $this->getSkuRuleParser($type);
            if (is_null($skuRuleParser)) {
                continue;
            }

            // 解析出需求请求的接口信息
            $apiInfo = $skuRuleParser->buildAsyncApiInfo($skuInfo);
            if (empty($apiInfo)) {
                continue;
            }

            $apiInfo[self::KEY_API_SKU_ID] = $skuInfo[self::KEY_SKU_RULE_ID];
            $apiInfo[self::KEY_API_PARAMS] = array_merge($apiInfo[self::KEY_API_PARAMS], ['parserModel' =>
                $this->pageParser->getParseModel()]);
            $apiInfoList[] = $apiInfo;
        }

        return $apiInfoList;
    }

    /**
     * 转换根据SKU数据字段API请求结果
     *
     * @return array
     */
    public function transformResult()
    {
        $uiTransAsyncInfoList = [];
        $componentId = $this->uiTplParser->getComponentId();
        $apiInfoList = $this->pageParser->getComponentRequestApiInfo($componentId);
        $apiResultListRefer = & $this->pageParser->getComponentApiResultRefer($componentId);
        if (empty($apiInfoList) || empty($apiResultListRefer)) {
            return [];
        }

        foreach ($apiInfoList as $apiId => $apiInfo) {
            if (!isset($apiInfo[self::KEY_API_SKU_ID])
                || !isset($this->skuIdToSkuInfoRefer[$apiInfo[self::KEY_API_SKU_ID]])
            ) {
                continue;
            }

            // 没有找到API请求结果或请求结果不是数组
            if (!isset($apiResultListRefer[$apiId]) || !is_array($apiResultListRefer[$apiId])) {
                continue;
            }

            // 没有获取到对应SKU规则解析器
            $ruleSkuInfo = $this->skuIdToSkuInfoRefer[$apiInfo[self::KEY_API_SKU_ID]];
            $type = (int)$ruleSkuInfo[self::KEY_SKU_RULE_TYPE];
            $skuRuleParser = $this->getSkuRuleParser($type);
            if (is_null($skuRuleParser)) {
                continue;
            }

            // 转换API返回结果
            $ruleAsyncInfo = $skuRuleParser->transformResult($apiResultListRefer[$apiId]);
            if (!empty($ruleAsyncInfo) && is_array($ruleAsyncInfo)) {
                $uiTransAsyncInfoList[] = array_merge($ruleSkuInfo, $ruleAsyncInfo);
            }
        }

        return $uiTransAsyncInfoList;
    }
}

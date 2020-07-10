<?php
namespace App\Services\NativePage\UiTplParser\Sku\Provider;

use App\Services\NativePage\PageUiAsyncDataParser;
use App\Services\NativePage\UiTplParser\AbstractUiTplAsyncApiParser;

/**
 * 商品信息来源解析
 *
 * @author TianHaisen
 */
abstract class AbstractSkuProvider
{
    /** @var PageUiAsyncDataParser 页面解析器 */
    protected $pageParser;

    /** @var AbstractUiTplAsyncApiParser 组件解析器 */
    protected $uiTplParser;

    /**
     * 构造函数
     *
     * @param PageUiAsyncDataParser $pageParser 页面解析器
     * @param AbstractUiTplAsyncApiParser $uiTplParser 组件解析器
     */
    public function __construct(PageUiAsyncDataParser $pageParser, AbstractUiTplAsyncApiParser $uiTplParser)
    {
        $this->pageParser = $pageParser;
        $this->uiTplParser = $uiTplParser;
    }

    /**
     * 根据SKU数据字段生成 API 请求信息, 在这里生成ES搜索参数，
     *
     * @param array $skuInfo 单个SKU配置信息
     * @return array
     */
    public abstract function buildAsyncApiInfo($skuInfo);

    /**
     * 转换API请求结果
     *
     * @param array API返回结果引用
     * @return array
     */
    public abstract function transformResult(&$apiResultRefer);

}

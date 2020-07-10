<?php
namespace App\Services\NativePage;

/**
 * PC/M 非原生页面组件数据转换器
 *
 * @author TianHaisen
 */
class WebDataTransformer
{
    /** @var PageUiAsyncDataParser 页面解析器 */
    protected $pageParser;

    /** @var array App 组件数据 */
    protected $componentData = [];

    /** @var array 引用空数组 */
    private $referEmptyArray = [];

    /**
     * 构造函数
     *
     * @param PageUiAsyncDataParser $pageParser 页面数据解析对象
     */
    public function __construct(PageUiAsyncDataParser $pageParser)
    {
        $this->pageParser = $pageParser;
    }

    /**
     * 返回APP组件数据,注意：这是一个引用
     *
     * @param int $componentId 组件ID
     * @return array
     */
    public function &getComponentDataRefer($componentId)
    {
        if (isset($this->componentData[$componentId])) {
            return $this->componentData[$componentId];
        }
        return $this->referEmptyArray;
    }

    /**
     * 设置组件异步数据
     *
     * @param int $componentId 组件ID
     * @param array $asyncInfo 组件异步数据
     */
    public function setComponentAsyncInfo($componentId, $asyncInfo)
    {
        if ($this->pageParser->getPageInfo()->hasComponent($componentId)) {
            $this->componentData[$componentId] = $asyncInfo;
        }
    }

    /**
     * 转换页面组件异步数据
     */
    public function transformUiAsyncData()
    {
        $this->pageParser->getPageInfo()->componentEach(function ($componentId) {
            // 转换异步数据格式
            $tplAsyncDataParser = $this->pageParser->getUiTplAsyncDataParser($componentId);
            $tplAsyncDataParser && $tplAsyncDataParser->transformWebData();
        });
    }

    /**
     * 获取APP组件异步数据
     *
     * @return array
     */
    public function getAsyncInfo()
    {
        if (empty($this->componentData)) {
            return [];
        }

        return $this->componentData;
    }
}

<?php
namespace App\Services\EsSearch;

use App\Base\KeyConstants;
use App\Services\Site\IBaseDataTransformer;
use App\Services\Site\AbstractApiResultTransformer;

/**
 * ES 搜索结果转换抽象类
 *
 * @author tianhaishen
 */
abstract class AbstractTransformer extends AbstractApiResultTransformer
{
    /** @var string 语言简码，如 en/fr */
    protected $lang;

    /**
     * 构造函数
     *
     * @param string $siteCode 站点简码
     * @param string $lang 语言简码
     */
    public function __construct($siteCode, $lang)
    {
        parent::__construct($siteCode);
        $this->lang = $lang;
    }

    /**
     * 获取搜索结果分页信息
     *
     * @param array $resultRefer ES 搜索结果
     * @return array
     */
    public function getPaginationInfo(&$resultRefer)
    {
        return [
            KeyConstants::PAGE_NUM      => (int)$resultRefer['pageNo'],
            KeyConstants::PAGE_SIZE     => (int)$resultRefer['pageSize'],
            KeyConstants::TOTAL_COUNT   => (int)$resultRefer['total'],
        ];
    }

    /**
     * 转换属性信息
     *
     * @param array $params 传入参数
     * @param array $resultRefer ES搜索结果
     * @return array
     */
    public function attrInfo(array $params, array &$resultRefer)
    {
        // 跳过没有属性字段或没有商品数据
        if ((isset($resultRefer[AbstractEsSearch::RESULT_KEY_GOODS]) && empty($resultRefer[AbstractEsSearch::RESULT_KEY_GOODS]))
            || !isset($resultRefer[AbstractEsSearch::RESULT_KEY_AGG])
        ) {
            return [];
        }

        $siteAttrs = [];
        if (isset($params[AbstractEsSearch::PARAM_NAME_SITE_ATTRS])
            && is_array($params[AbstractEsSearch::PARAM_NAME_SITE_ATTRS])
        ) {
            $siteAttrs = array_column($params[AbstractEsSearch::PARAM_NAME_SITE_ATTRS], null, 'title');
            $siteAttrs[SearchParamBuilder::ATTR_FIELD_NAME_PRICE] = [
                'name' => 'Price',
                'title' => __('common.site_attr_price'),
                'type' => IBaseDataTransformer::ATTR_SHOW_TYPE_PRICE
            ];
        }

        $allows = [
            AbstractEsSearch::ROOT_CATEGORY_KEY
        ];

        $itemList = [];
        foreach ($resultRefer[AbstractEsSearch::RESULT_KEY_AGG] as $attrName => $valueList) {
            if (in_array($attrName, $allows, true)) {
                continue;
            }

            $isPrice = $attrName === SearchParamBuilder::ATTR_FIELD_NAME_PRICE;
            $childList = [];
            foreach ($valueList as $valueInfo) {
                $_key = $valueInfo['key'];
                if (empty($_key)) {
                    continue;
                }

                if ($isPrice
                    && isset($params[AbstractEsSearch::PARAM_NAME_AGGREGATIONS][SearchParamBuilder::ATTR_FIELD_NAME_PRICE])
                ) {
                    $_priceValues = array_column(
                        $params[AbstractEsSearch::PARAM_NAME_AGGREGATIONS][SearchParamBuilder::ATTR_FIELD_NAME_PRICE],
                        null,
                        'key'
                    );

                    $childList[] = [
                        'item_id' => $_key,
                        'item_title' => $_priceValues[$_key]['from'] .' - '. $_priceValues[$_key]['to'],
                        'price_min' => $_priceValues[$_key]['from'],
                        'price_max' => $_priceValues[$_key]['to']
                    ];
                } else {
                    $childList[] = [
                        'item_id' => $valueInfo['key'],
                        'item_title' => $valueInfo['key'],
                    ];
                }
            }

            $_attrId = $_attrTitle = $attrName;
            $_attrType = IBaseDataTransformer::ATTR_SHOW_TYPE_NORMAL;
            if (isset($siteAttrs[$attrName])) {
                $_attrId = $siteAttrs[$attrName]['name'];
                $_attrTitle = $siteAttrs[$attrName]['title'];
                $_attrType = $siteAttrs[$attrName]['type'];
            }

            if (empty($_attrId) || empty($attrName)) {
                continue;
            }

            $itemList[] = [
                'item_id' => $_attrId,
                'item_title' => $_attrTitle,
                'item_type' => $_attrType,
                'child_item' => $childList
            ];
        }

        return $itemList;
    }


    /**
     * 将ES商品信息转换为Geshop系统商品信息格式
 * @param array $goodsInfoListRefer 商品列表
     * @return array
     */
    public abstract function goodsInfo(array &$goodsInfoListRefer);

    /**
     * 将ES分类信息转换为站点分类
     *
     * @param array $categoryInfoListRefer 分类列表
     * @return array
     */
    public abstract function categoryInfo(array &$categoryInfoListRefer);


}

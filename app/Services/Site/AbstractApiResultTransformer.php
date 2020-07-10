<?php
namespace App\Services\Site;

use App\Base\ApiConstants;
use App\Base\GoodsConstants;
use App\Exceptions\ApiRequestException;
use App\Helpers\ShopHelpers;
use App\Helpers\SiteHelpers;

/**
 * 处理API接口返回
 *
 * @author tianhaishen
 */
abstract class AbstractApiResultTransformer
{
    /** @var string 站点简码，如: zf-pc/zf-app */
    protected $siteCode;

    /** @var string 网站简码, 如: zf */
    protected $websiteCode;

    /** @var string 平台简码, 如: wap */
    protected $platformCode;

    /**
     * 构造函数
     *
     * @param string $siteCode 站点简码，如: zf-pc/zf-app
     */
    public function __construct($siteCode)
    {
        $this->siteCode = $siteCode;
        list($this->websiteCode, $this->platformCode) = SiteHelpers::splitSiteCode($this->siteCode);
    }
    /**
     * 检查服装站点通用接口是否成功返回
     *
     * @param array $apiResultRefer 接口返回数据引用
     * @throws ApiRequestException
     */
    public function checkCommonApiSuccessResult(&$apiResultRefer)
    {
        if (!array_key_exists('code', $apiResultRefer)
            || !array_key_exists('message', $apiResultRefer)
            || !array_key_exists('data', $apiResultRefer)
        ) {
            throw new ApiRequestException('API接口返回不是标准格式!');
        }

        if ((int)$apiResultRefer['code'] !== 0) {
            throw new ApiRequestException('请求接口错误:'. $apiResultRefer['message']);
        }
    }

    /**
     * 检查ES搜索接口是否成功返回
     *
     * @param array $apiResultRefer 接口返回数据引用
     * @throws ApiRequestException
     */
    public function checkEsSearchApiSuccessResult(&$apiResultRefer)
    {
        if (!array_key_exists('code', $apiResultRefer)
            || !array_key_exists('msg', $apiResultRefer)
        ) {
            throw new ApiRequestException('ES搜索接口返回不是标准格式!');
        }

        if ((int)$apiResultRefer['code'] !== 0) {
            throw new ApiRequestException('请求接口错误:'. $apiResultRefer['msg']);
        }
    }

    /**
     * 检查活动页面组件内异步数据接口是否成功返回
     *
     * @param array $apiInfoRefer API调用信息引用
     * @param array $apiResultRefer 接口返回数据引用
     * @throws ApiRequestException
     * @see \App\Services\NativePage\AsyncApiRequest
     */
    public function checkUiComponentApiSuccessResult($apiInfoRefer, &$apiResultRefer)
    {
        if (ApiConstants::API_NAME_ES_SEARCH === $apiInfoRefer['api']) {
            $this->checkEsSearchApiSuccessResult($apiResultRefer);
        } else {
            $this->checkCommonApiSuccessResult($apiResultRefer);
        }
    }

    /**
     * 获取商品详情接口商品商品列表数据
     *
     * @param array $apiResultRefer 接口返回数据引用
     * @param bool $isTransStandard 是否API层标准格式
     * @return array
     */
    public function getGoodsGetDetailGoodsInfo(&$apiResultRefer, $isTransStandard = true)
    {
        if (!isset($apiResultRefer['data']['goodsInfo'])) {
            return [];
        }

        // 不是标准格式原样返回
        $goodsInfoList = $apiResultRefer['data']['goodsInfo'];
        if (!$isTransStandard) {
            return $goodsInfoList;
        }

        // API层标准格式转换
        $goodsList = [];
        foreach ($goodsInfoList as $goodsInfo) {
            $goodsList[] = [
                GoodsConstants::GOODS_ID        => $goodsInfo['goods_id'],
                GoodsConstants::GOODS_SN        => $goodsInfo['goods_sn'],
                GoodsConstants::GOODS_TITLE     => $goodsInfo['goods_title'],
                GoodsConstants::GOODS_IMG       => $goodsInfo['goods_img'],
                GoodsConstants::DETAIL_URL      => $goodsInfo['url_title'],
                GoodsConstants::QUICK_BUY_URL   => $goodsInfo['url_quick'],
                GoodsConstants::SHOP_PRICE      => $goodsInfo['shop_price'],
                GoodsConstants::MARKET_PRICE    => $goodsInfo['market_price'],
                GoodsConstants::DISCOUNT        => $goodsInfo['discount'],
                GoodsConstants::STOCK_NUM       => $goodsInfo['goods_number'],
                GoodsConstants::PROMOTIONS      => $goodsInfo['promotions'],
            ];
        }
        return $goodsList;
    }

    /**
     * 服装站点公共接口数据转换
     *
     * @param array $apiResultRefer 接口返回数据引用
     * @return array
     */
    protected function transCommonApiResult(&$apiResultRefer)
    {
        if (isset($apiResultRefer['data']) && is_array($apiResultRefer['data'])) {
            return $apiResultRefer['data'];
        }
        return [];
    }
}

<?php
namespace App\Http\Controllers\Web\Base;

use App\Services\RTPrice\RealTimePrice;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Base\AppConstants;
use App\Exceptions\ApiRequestException;
use App\Http\Controllers\AbstractWebController;
use App\Services\NativePage\NativePageInfo;
use App\Services\NativePage\PageUiAsyncDataParser;
use App\Services\NativePage\UiTplParser\Sku\SkuDataParser;
use App\Services\NativePage\UiTplParser\Sku\Provider\SopSkuProvider;
use App\Services\NativePage\UiTplParser\AbstractUiTplAsyncApiParser;
use App\Services\NativePage\UiTplParser\Web_biComponentParser;

/**
 * PC/M端商品接口抽象类
 *
 * @author TianHaisen
 */
abstract class AbstractGoodsController extends AbstractWebController
{
    /**
     * 获取商品运营平台商品列表
     *
     * @return JsonResponse
     */
    public function getSopGoodsDetail()
    {
        $params = request()->all();
        $validator = Validator::make($params, [
            'site_code' => 'required|alpha_dash|max:10',
            'pipeline' => 'required|alpha_num|max:10',
            'lang' => 'required|alpha_dash|max:10',
            'rule_id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('确少必要参数', 1001);
        }

        // 组件参数
        $uiParamNames = [
            Web_biComponentParser::HTTP_PARAM_PAGE_NO,
            Web_biComponentParser::HTTP_PARAM_COOKIE_ID,
            Web_biComponentParser::HTTP_PARAM_BTS_UNIQUE_ID,
            Web_biComponentParser::HTTP_PARAM_COUNTRY_CODE,
            Web_biComponentParser::HTTP_PARAM_AGENT,
            Web_biComponentParser::HTTP_PARAM_SORT_ID,
            Web_biComponentParser::HTTP_PARAM_PAGE_SIZE,
        ];
        $uiHttpParams = $this->getComponentHttpParams($params, $uiParamNames);
        $pageId = 1;
        $componentId = '1573012273441';
        $envType = isset($params['env']) ? $params['env'] : 1;
        $skuInfo = [
            SkuDataParser::KEY_SKU_RULE_ID    => time(),
            SkuDataParser::KEY_SKU_RULE_TYPE  => AppConstants::SKU_FROM_SOP,
            SopSkuProvider::KEY_SOP_RULE_ID   => $params['rule_id'],
            'component_id' => $componentId,
        ];
        $simulationUiData = [
            'page_id' => $pageId,
            'component_id' => $componentId,
            'component_key' => 'Web',
            'tpl_id' => 1,
            'tpl_name' => 'biComponent',
            NativePageInfo::UI_KEY_SKU_DATA => [$skuInfo]
        ];

        try {
            $pageInfo = new NativePageInfo($params['site_code'], $pageId, $params['pipeline'], $params['lang'], [$simulationUiData]);
            $pageParser = new PageUiAsyncDataParser($pageInfo, $uiHttpParams);
            //设置解析模式为发布模式
            ($envType > 1) &&$pageParser->setParseModel(NativePageInfo::PARSE_MODEL_PUBLISH);
            $pageParser->parseUiComponentAsyncData();

            $webDataTransformer = $pageParser->getWebDataTransformer();
            $webDataTransformer->transformUiAsyncData();
            $asyncInfo = $webDataTransformer->getAsyncInfo();
            $jsonData = $asyncInfo[$componentId] ?? [];
        } catch (ApiRequestException $e) {
            $jsonData = [];
        }

        return $this->jsonSuccess($jsonData);
    }

    /**
     * 获取自动刷新组件商品列表
     *
     * @return JsonResponse
     */
    public function getAutoRefreshUiGoodsList()
    {
        $params = request()->all();
        $validator = Validator::make($params, [
            'site_code' => 'required|alpha_dash|max:10',
            'pipeline' => 'required|alpha_num|max:10',
            'lang' => 'required|alpha_dash|max:10',
            'page_id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('缺少必要参数', 1001);
        }

        $pageId = (int)$params['page_id'];
        $realTimePrice = new RealTimePrice($params['site_code'], $params['lang'], $params['pipeline']);
        $jsonData = $realTimePrice->getUiAsyncData($pageId);
        return $this->jsonSuccess($jsonData);
    }
}

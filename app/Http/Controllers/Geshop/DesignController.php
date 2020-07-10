<?php
namespace App\Http\Controllers\Geshop;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Helpers\BeanHelpers;
use App\Helpers\AppHelpers;
use App\Helpers\SiteHelpers;
use App\Http\Controllers\AbstractWebController;
use App\Exceptions\ApiRequestException;
use App\Combines\NativePageCombines;
use App\Services\NativePage\NativePageInfo;
use App\Services\NativePage\PageUiAsyncDataParser;
use App\Services\NativePage\UiTplParser\AbstractUiTplAsyncApiParser;

/**
 * 装修页面接口
 *
 * @author TianHaisen
 */
class DesignController extends AbstractWebController
{
    /**
     * 解析组件goodsSKU字段商品信息配置，并返回商品详情信息
     *
     * @return JsonResponse
     * @throws ApiRequestException
     */
    public function goodsInfo()
    {
        $params = request()->all();
        $validator = Validator::make($params, [
            'site_code' => 'required|alpha_dash|max:10',
            'page_id' => 'required|integer',
            'pipeline' => 'required|alpha_num|max:10',
            'lang' => 'required|alpha_dash|max:10',
            'sku_info' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonFail('确少必要参数', 1001);
        }

        $siteCode = $params['site_code'];
        $pageId = $params['page_id'];
        $pipeline = $params['pipeline'];
        $lang = $params['lang'];
        $skuInfo = AppHelpers::jsonDecode($params['sku_info'], true);

        $componentId = '1573012273441';
        $simulationUiData = [
            'page_id' => $pageId,
            'component_id' => $componentId,
            'component_key' => 'Geshop',
            'tpl_id' => 1,
            'tpl_name' => 'design',
            NativePageInfo::UI_KEY_SKU_DATA => $skuInfo
        ];
        $pageInfo = new NativePageInfo($siteCode, $pageId, $pipeline, $lang, [$simulationUiData]);
        $pageParser = new PageUiAsyncDataParser($pageInfo);
        $pageParser->setIsClearApiResultCache(true);
        $pageParser->setParseModel(NativePageInfo::PARSE_MODEL_DESIGN);
        $pageParser->parseUiComponentAsyncData();

        if (GES_ENABLE_TRACK_LOG) {
            return $this->jsonSuccess($pageParser->getAsyncInfo());
        }

        $webDataTransformer = $pageParser->getWebDataTransformer();
        $webDataTransformer->transformUiAsyncData();
        $asyncInfo = $webDataTransformer->getAsyncInfo();
        if (isset($asyncInfo[$componentId][AbstractUiTplAsyncApiParser::UI_ASYNC_SKU_INFO])) {
            return $this->jsonSuccess($asyncInfo[$componentId][AbstractUiTplAsyncApiParser::UI_ASYNC_SKU_INFO]);
        }
        return $this->jsonSuccess($skuInfo);
    }

    /**
     * 装修页面和预览页面异步数据
     *
     * @return JsonResponse
     */
    public function asyncInfo()
    {
        $params = request()->all();
        $validator = Validator::make($params, [
            'site_code' => 'required|alpha_dash|max:10',
            'page_id' => 'required|integer',
            'pipeline' => 'required|alpha_num|max:10',
            'lang' => 'required|alpha_dash|max:10',
            'user_group' => 'required|boolean'
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('确少必要参数', 1001);
        }

        $siteCode = $params['site_code'];
        $pageId = $params['page_id'];
        $pipeline = $params['pipeline'];
        $lang = $params['lang'];
        $userGroup = $params['user_group'];
        $envType = $params['env'] ?? 1; // 环境变量，1=装修，2=预览, 3=发布
        $componentIds = (isset($params['component_id']) && !empty($params['component_id']))
            ? explode(',', $params['component_id'])
            : [];

        $envType = (int)$envType;
        app()->setLocale($lang);

        /** @var NativePageCombines $nativePageCombines */
        $nativePageCombines = app(NativePageCombines::class);
        $componentData = $nativePageCombines->getComponentData($pageId, $lang);
        if (!empty($componentData) && is_array($componentData)) {
            $pageInfo = new NativePageInfo($siteCode, $pageId, $pipeline, $lang, $componentData);
            $pageInfo->setFilterComponentIds($componentIds);
            ($envType > 1) && $pageInfo->setUserGroup($userGroup);

            $pageParser = new PageUiAsyncDataParser($pageInfo, $params);
            $pageParser->setIsClearApiResultCache(true);
            try {
                ($envType > 1) && $pageParser->setParseModel(NativePageInfo::PARSE_MODEL_PREVIEW);
                $pageParser->parseUiComponentAsyncData();

                $webDataTransformer = $pageParser->getWebDataTransformer();
                $webDataTransformer->transformUiAsyncData();
                return $this->jsonSuccess($webDataTransformer->getAsyncInfo());
            } catch (\Throwable $e) {
                $format = "%s in %s line %d trace:\n%s";
                $message = sprintf($format, $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString());
                return $this->jsonFail('服务异常', 1, ['msg' => $message]);
            }
        }

        return $this->jsonSuccess([]);
    }

    /**
     * 获取站点ES搜索支持排序列表
     *
     * @return JsonResponse
     */
    public function esSearchSortByList()
    {
        $params = request()->all();
        $validator = Validator::make($params, [
            'site_code' => 'required|alpha_dash|max:10',
            'lang' => 'required|alpha_dash|max:10',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('确少必要参数', 1001);
        }

        $pipeline = $params['pipeline'] ?? '';
        $lang = $params['lang'];

        //app()->setLocale($lang);
        $esSearch = BeanHelpers::getEsSearch($params['site_code'], $lang, $pipeline);
        return $this->jsonSuccess($esSearch->getSortByList());
    }


    /**
     * M和APP活动页面兜底数据
     *
     * @return JsonResponse
     */
    public function fallback()
    {
        set_time_limit(60);

        $params = request()->all();
        $validator = Validator::make($params, [
            'wap' => 'required',
            'app' => 'required',
            'pipeline' => 'required|alpha_num|max:10',
            'lang' => 'required|alpha_dash|max:10',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('确少必要参数', 1001);
        }

        $appInfo = AppHelpers::jsonDecode($params['app'], true);
        $wapInfo = AppHelpers::jsonDecode($params['wap'], true);
        $pipeline = $params['pipeline'];
        $lang = $params['lang'];
        $userGroup = $params['user_group'] ?? 0;

        try {
            app()->setLocale($lang);

            list($websiteCode, ) = SiteHelpers::splitSiteCode($appInfo['site_code']);
            $defaultLang = config(sprintf('site.%s.pipelineDefaultLang.%s', $websiteCode, $pipeline));

            // 获取组件数据
            /** @var NativePageCombines $nativePageCombines */
            $nativePageCombines = app(NativePageCombines::class);
            list(, $pageUiData) = $nativePageCombines->getUseComponentData($appInfo['page_id'], $lang, $defaultLang);
            if (empty($pageUiData)) {
                return $this->jsonSuccess(['wap' => [], 'app' => []]);
            }

            // 原生页面对象
            $pageInfo = new NativePageInfo($appInfo['site_code'], $appInfo['page_id'], $pipeline, $lang, $pageUiData);
            $pageInfo->setUserGroup($userGroup);

            // 解析组件数据
            $pageParser = new PageUiAsyncDataParser($pageInfo, $params);
            $pageParser->setIsClearApiResultCache(true);
            $pageParser->getAsyncDataSupplement()->setThrowOnGoodsPriceApiFail(true);
            $pageParser->parseUiComponentAsyncData();
            $pageParser->fallbackAsyncInfo();

            // Wap端json数据格式
            $wapJsonData = [
                'code'    => 0,
                'message' => 'success',
                'data'    => $pageParser->getAsyncInfo()
            ];

            // App端json数据格式
            $appData = $pageParser->getAppDataTransformer()->conversionToApp();
            $appJsonData = [
                'statusCode' => 200,
                'msg' => 'Success',
                'af_params' => [],
                'result' => $appData
            ];

            // 清除关联平台页面组件接口缓存数据
            $apiCacheManager = $pageParser->getApiCacheManager();
            $apiCacheManager->switchToRelatedPage($wapInfo['site_code'], $wapInfo['page_id']);
            $apiCacheManager->clearAllComponentApiResultCache();

            return $this->jsonSuccess(['wap' => $wapJsonData, 'app' => $appJsonData]);
        } catch (\Throwable $e) {
            $format = "%s in %s line %d trace:\n%s";
            $message = sprintf($format, $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString());
            return $this->jsonFail('服务异常:'. $e->getMessage(), 1, ['msg' => $message]);
        }

    }
}

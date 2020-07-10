<?php
namespace App\Http\Controllers\App\Activity;

use App\Base\AppConstants;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\ApiRequestException;
use App\Services\NativePage\NativePageInfo;
use App\Services\NativePage\PageDataFallback;
use App\Services\NativePage\PageUiAsyncDataParser;
use App\Http\Controllers\App\AbstractAppController;

/**
 * App原生活动页面接口
 *
 * @author TianHaisen
 */
class PageController extends AbstractAppController
{
    /**
     * 获取页面内所有组件的异步数据信息
     *
     * @param int $id 页面ID
     * @return JsonResponse
     */
    public function show($id)
    {
        $params = request()->post();
        $validator = Validator::make($params, [
            'site_code' => 'required|alpha_dash|max:10',
            'pipeline' => 'required|alpha_num|max:10',
            'lang' => 'required|alpha_dash|max:10',
            'user_group' => 'required|boolean'
        ]);
        if ($validator->fails()) {
            return $this->apiJsonFail('The parameter is invalid.');
        }

        $id = (int)trim($id);
        $siteCode = $params['site_code'];
        $pipeline = $params['pipeline'];
        $lang = $params['lang'];
        $userGroup = $params['user_group'];

        try {
            app()->setLocale($lang);
            $pageInfo = new NativePageInfo($siteCode, $id, $pipeline, $lang);
            $pageInfo->setUserGroup($userGroup);
            $pageParser = new PageUiAsyncDataParser($pageInfo, $params);
            $pageParser->setParseModel(NativePageInfo::PARSE_MODEL_PUBLISH);
            $pageParser->parseUiComponentAsyncData();

            $appData = $pageParser->getAppDataTransformer()->conversionToApp();
            $btsInfo = $pageParser->getBtsResultInfo();

            $jsonData = [
                'af_params' => $btsInfo,
                'result' => $appData
            ];
            return $this->apiJsonSuccess($jsonData);
        } catch (ApiRequestException $e) {
            // API请求失败，使用兜底数据
            $fallback = new PageDataFallback($siteCode, $id, $pipeline, $lang);
            $appData = $fallback->getAppData();
            $jsonData = [
                'af_params' => [],
                'result' => $appData
            ];

            $_msg = '页面[ %s-%d-%s-%s ]请求API失败，使用兜底数据(%s).';
            ges_error_log(__CLASS__, $_msg, $siteCode, $id, $pipeline, $lang, $fallback->getFallbackDataUrl());
            return $this->apiJsonSuccess($jsonData);
        }
    }

    /**
     * 获取活动页面详情静态数据
     *
     * @return JsonResponse
     */
    public function detail()
    {
        $params = request()->post();
        if (($checkResult = $this->checkRequiredParams($params)) !== null) {
            return $checkResult;
        }

        $siteCode = $params['site_code'];
        $pageId = (int)trim($params['page_id']);
        $pipeline = $params['pipeline'];
        $lang = $params['lang'];
        $userGroup = $params['user_group'];

        app()->setLocale($lang);
        $pageInfo = new NativePageInfo($siteCode, $pageId, $pipeline, $lang);
        $pageInfo->setUserGroup($userGroup);
        $pageParser = new PageUiAsyncDataParser($pageInfo, $params);
        $pageParser->setParseModel(NativePageInfo::PARSE_MODEL_PUBLISH);
        $pageParser->setApiVersion(AppConstants::API_VERSION_V2);

        $appTransformer = $pageParser->getAppDataTransformer();
        $appTransformer->transformAppUiStaticData();
        $jsonData = $appTransformer->getAppData();
        return $this->apiJsonSuccess($jsonData);
    }

    /**
     * 获取页面内所有组件的异步数据信息
     *
     * @return JsonResponse
     */
    public function asyncInfo()
    {
        $params = request()->post();
        if (($checkResult = $this->checkRequiredParams($params)) !== null) {
            return $checkResult;
        }

        $siteCode = $params['site_code'];
        $pageId = (int)trim($params['page_id']);
        $pipeline = $params['pipeline'];
        $lang = $params['lang'];
        $userGroup = $params['user_group'];
        $componentIds = (isset($params['component_id']) && !empty($params['component_id']))
            ? explode(',', $params['component_id'])
            : [];

        try {
            app()->setLocale($lang);
            $pageInfo = new NativePageInfo($siteCode, $pageId, $pipeline, $lang);
            $pageInfo->setFilterComponentIds($componentIds);
            $pageInfo->setUserGroup($userGroup);

            $pageParser = new PageUiAsyncDataParser($pageInfo, $params);
            $pageParser->setParseModel(NativePageInfo::PARSE_MODEL_PUBLISH);
            $pageParser->setApiVersion(AppConstants::API_VERSION_V2);
            $pageParser->parseUiComponentAsyncData();

            $appTransformer = $pageParser->getAppDataTransformer();
            $appTransformer->transformAppUiAsyncData();

            $jsonData = $appTransformer->getAsyncInfo();
            $jsonData['af_params'] = $pageParser->getBtsResultInfo();
            return $this->apiJsonSuccess($jsonData);
        } catch (ApiRequestException $e) {
            // API请求失败，使用兜底数据
            $fallback = new PageDataFallback($siteCode, $pageId, $pipeline, $lang);
            $appAsyncInfo = $fallback->getAppAsyncInfo();
            $appAsyncInfo['af_params'] = [];
            $_msg = '页面[ %s-%d-%s-%s ]请求API失败，使用兜底数据(%s).';
            ges_error_log(__CLASS__, $_msg, $siteCode, $pageId, $pipeline, $lang, $fallback->getFallbackDataUrl());
            return $this->apiJsonSuccess($appAsyncInfo);
        }
    }

    /**
     * 检查必填参数
     *
     * @param array $params
     * @return JsonResponse|null
     */
    protected function checkRequiredParams($params)
    {
        $validator = Validator::make($params, [
            'site_code' => 'required|alpha_dash|max:10',
            'page_id' => 'required|integer',
            'pipeline' => 'required|alpha_num|max:10',
            'lang' => 'required|alpha_dash|max:10',
            'user_group' => 'required|boolean'
        ]);
        if ($validator->fails()) {
            return $this->apiJsonFail('The parameter is invalid.');
        }
        return null;
    }
}

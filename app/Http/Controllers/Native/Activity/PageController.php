<?php
namespace App\Http\Controllers\Native\Activity;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\ApiRequestException;
use App\Http\Controllers\AbstractWebController;
use App\Services\NativePage\NativePageInfo;
use App\Services\NativePage\PageDataFallback;
use App\Services\NativePage\PageUiAsyncDataParser;

/**
 * M端原生活动页面接口
 *
 * @author TianHaisen
 */
class PageController extends AbstractWebController
{
    /**
     * 获取页面内所有组件的异步数据信息
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
            $pageParser->parseUiComponentAsyncData();

            $webDataTransformer = $pageParser->getWebDataTransformer();
            $webDataTransformer->transformUiAsyncData();
            return $this->jsonSuccess($webDataTransformer->getAsyncInfo());
        } catch (ApiRequestException $e) {
            // API请求失败，使用兜底数据
            $fallback = new PageDataFallback($siteCode, $pageId, $pipeline, $lang);
            $wapData = $fallback->getWapData();
            $_msg = '页面[ %s-%d-%s-%s ]请求API失败，使用兜底数据(%s).';
            ges_warning_log(__CLASS__, $_msg, $siteCode, $pageId, $pipeline, $lang, $fallback->getFallbackDataUrl());
            return $this->jsonSuccess($wapData);
        }
    }
}

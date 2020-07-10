<?php
namespace App\Combines;

use App\Helpers\AppHelpers;
use App\Models\NativePageLayoutComponentModel;
use App\Models\NativePageUiComponentModel;
use App\Services\NativePage\NativePageInfo;

/**
 * 原生页面业务层数据
 *
 * @author TianHaisen
 */
class NativePageCombines extends AbstractCombines
{
    /**
     * 获取页面组件数据，如果当前语言没有装修使用默认语言简码数据
     *
     * @param int $pageId 页面ID
     * @param string $currentLang 当前语言简码
     * @param string $defaultLang 默认语言简码
     * @return array
     */
    public function getUseComponentData($pageId, $currentLang, $defaultLang)
    {
        $langCode = $currentLang;
        $uiIds = NativePageLayoutComponentModel::getNativePageLayouts($pageId, $langCode);
        if (empty($uiIds) && ($currentLang !== $defaultLang)) {
            $langCode = $defaultLang;
            $uiIds = NativePageLayoutComponentModel::getNativePageLayouts($pageId, $langCode);
        }

        if (!empty($uiIds) && is_array($uiIds)) {
            $componentsData = $this->getNativePageUiData($pageId, $langCode, $uiIds);
            if (!empty($componentsData) && is_array($componentsData)) {
                return [$langCode, $componentsData];
            }
        }

        return [$currentLang, []];
    }

    /**
     * 获取页面指定语言的组件数据
     *
     * @param int $pageId 页面ID
     * @param string $lang 语言简码
     * @return array
     */
    public function getComponentData($pageId, $lang)
    {
        $uiIds = NativePageLayoutComponentModel::getNativePageLayouts($pageId, $lang);
        if (!empty($uiIds) && is_array($uiIds)) {
            $componentsData = $this->getNativePageUiData($pageId, $lang, $uiIds);
            if (!empty($componentsData) && is_array($componentsData)) {
                return $componentsData;
            }
        }

        return [];
    }

    /**
     * 获取页面组件数据
     *
     * @param int $pageId
     * @param string $lang
     * @param array $uiIds
     * @return array
     */
    protected function getNativePageUiData($pageId, $lang, $uiIds)
    {
        $componentsData = NativePageUiComponentModel::getNativePageUiData($pageId, $lang, $uiIds);
        if (!empty($componentsData) && is_array($componentsData)) {
            $jsonFields = [
                NativePageInfo::UI_KEY_STYLE_DATA,
                NativePageInfo::UI_KEY_SKU_DATA,
                NativePageInfo::UI_KEY_SETTING_DATA
            ];
            foreach ($componentsData as &$uiDataRefer) {
                foreach ($uiDataRefer as $fieldName => &$valueRefer) {
                    if (is_string($valueRefer)
                        && !empty($valueRefer)
                        && in_array($fieldName, $jsonFields, true))
                    {
                        $valueRefer = AppHelpers::jsonDecode($valueRefer, true);
                    }
                }
            }
            return $componentsData;
        }

        return [];
    }
}

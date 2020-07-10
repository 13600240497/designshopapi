<?php
namespace App\Services\NativePage;

use App\Base\AppConstants;

/**
 * APP组件数据转换器
 *
 * @author TianHaisen
 */
class AppDataTransformer
{
    /** @var array geshop组件转换层APP组件ID关系表 */
    const COMPONENT_KEY_MAP = [
        'U000242' => 100,
        'U000243' => 101,
        'U000244' => 102,
        'U000245' => 103,
        'U000248' => 104,
        'U000249' => 103,
        'U000251' => 105,
        'U000256' => 106
    ];

    /** @var string 组件字段名称 - 异步信息字段名称 */
    const UI_KEY_COMPONENT_ASYNC = 'component_async';

    /** @var string 组件字段名称 - 配置数据 */
    const UI_KEY_COMPONENT_DATA = 'component_data';

    /** @var string 组件字段名称 - 样式数据 */
    const UI_KEY_COMPONENT_STYLE = 'component_style';


    /** @var PageUiAsyncDataParser 页面解析器 */
    protected $pageParser;

    /** @var array App 组件数据 */
    protected $componentData = [];

    /** @var array 引用空数组 */
    private $referEmptyArray = [];

    /** @var int 组件楼层 */
    private $floorNum = 1;

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
            $this->componentData[$componentId][self::UI_KEY_COMPONENT_ASYNC] = $asyncInfo;
        }
    }

    /**
     * 转换页面组件样式数据和配置数据
     */
    public function transformAppUiStaticData()
    {
        $this->floorNum = 1;
        $this->pageParser->getPageInfo()->componentEach(function ($componentId) {
            // 转换组件基础数据格式及样式
            $this->conversionStaticData($componentId);
            $this->floorNum++;
        });
    }

    /**
     * 转换页面组件异步数据
     */
    public function transformAppUiAsyncData()
    {
        $this->pageParser->getPageInfo()->componentEach(function ($componentId) {
            // 转换异步数据格式
            $tplAsyncDataParser = $this->pageParser->getUiTplAsyncDataParser($componentId);
            $tplAsyncDataParser && $tplAsyncDataParser->transformAppData();
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

        $asyncInfoRefer = [];
        $uiIds = array_keys($this->componentData);
        foreach ($uiIds as $componentId) {
            if (isset($this->componentData[$componentId][self::UI_KEY_COMPONENT_ASYNC])) {
                $asyncInfoRefer[$componentId] = & $this->componentData[$componentId][self::UI_KEY_COMPONENT_ASYNC];
            } else {
                $asyncInfoRefer[$componentId] = [];
            }
        }
        return $asyncInfoRefer;
    }

    /**
     * 获取APP所有组件数据
     *
     * @return array
     */
    public function getAppData()
    {
        return empty($this->componentData) ? [] : array_values($this->componentData);
    }

    /**
     * 转换APP数据格式
     *
     * @return array
     */
    public function conversionToApp()
    {
        // 转换APP数据格式
        $this->floorNum = 1;
        $this->pageParser->getPageInfo()->componentEach(function ($componentId) {
            // 1. 转换组件基础数据格式及样式
            $this->conversionStaticData($componentId);

            // 2. 转换异步数据格式
            $tplAsyncDataParser = $this->pageParser->getUiTplAsyncDataParser($componentId);
            if ($tplAsyncDataParser) {
                $tplAsyncDataParser->transformAppData();
            }
            $this->floorNum++;
        });

        return empty($this->componentData) ? [] : array_values($this->componentData);
    }

    /**
     * 转换组件基础数据格式及样式
     *
     * @param int $componentId 组件ID
     */
    protected function conversionStaticData($componentId)
    {
        $uiDataRefer = & $this->pageParser->getPageInfo()->getComponentDataRefer($componentId);
        if (empty($uiDataRefer) || !is_array($uiDataRefer)) {
            unset($uiDataRefer);
            return;
        }

        $componentKey = $uiDataRefer['component_key'];
        $temp = [
            'component_id'   => $componentId,
            'component_name'  => $uiDataRefer['component_name'] ?? '',
            'component_type' => self::COMPONENT_KEY_MAP[$componentKey] ?? '',
            self::UI_KEY_COMPONENT_DATA => $uiDataRefer[NativePageInfo::UI_KEY_SETTING_DATA] ?? [],
            self::UI_KEY_COMPONENT_STYLE => $uiDataRefer[NativePageInfo::UI_KEY_STYLE_DATA] ?? [],
        ];

        if (!empty($temp[self::UI_KEY_COMPONENT_STYLE])) {
            $roundKeys = [
                'margin_top', 'margin_bottom', 'padding_top', 'padding_bottom', 'width',
                'height', 'text_size', 'item_radius', 'bg_radius'
            ];
            foreach($temp['component_style'] as $key => &$value) {
                if (!empty($value) && in_array($key, $roundKeys)) {
                    $value = $this->getRound($value);
                }
            }
        }

        if (isset($uiDataRefer[NativePageInfo::UI_KEY_STYLE_DATA])) {
            $styleRefer = &$uiDataRefer[NativePageInfo::UI_KEY_STYLE_DATA];

            if (isset($styleRefer['shop_price_title'])) {
                $temp['shopPrice_style'] = [
                    'text'       => $styleRefer['shop_price_title'] ?? '',
                    'text_color' => $styleRefer['shop_price_color'] ?? ''
                ];
            }

            if (isset($styleRefer['discount_type'])) {
                $temp['discount_style'] = [
                    'show'          => $styleRefer['discount_show'] ?? '',
                    'type'          => $styleRefer['discount_type'] ?? '',
                    'width'         => !empty($styleRefer['discount_width']) ? $this->getRound($styleRefer['discount_width']) : '',
                    'height'        => !empty($styleRefer['discount_height']) ? $this->getRound($styleRefer['discount_height']) : '',
                    'margin_top'    => !empty($styleRefer['discount_margin_top']) ? $this->getRound($styleRefer['discount_margin_top']) : '',
                    'margin_right'  => !empty($styleRefer['discount_margin_right']) ? $this->getRound($styleRefer['discount_margin_right']) : '',
                    'bg_img'        => $styleRefer['discount_bg_image'] ?? '',
                    'bg_color'      => $styleRefer['discount_bg_color'] ?? '',
                    'text_color'    => $styleRefer['discount_font_color'] ?? '',
                    'padding_top'   => !empty($styleRefer['discount_padding_top']) ? $this->getRound($styleRefer['discount_padding_top']) : '',
                    'padding_right' => !empty($styleRefer['discount_padding_right']) ? $this->getRound($styleRefer['discount_padding_right']) : '',
                ];
            }
            unset($styleRefer);
        }

        if (isset($uiDataRefer[NativePageInfo::UI_KEY_SETTING_DATA]['list'])) {
            $settingData = $uiDataRefer[NativePageInfo::UI_KEY_SETTING_DATA];
            $this->markComponentFloor($settingData, $this->floorNum);
            if ($this->pageParser->getApiVersion() === AppConstants::API_VERSION_V1) {
                $temp['list'] = $settingData['list'] ?? '';
            }
        }
        $this->markComponentFloor($temp[self::UI_KEY_COMPONENT_DATA], $this->floorNum);

        unset($uiDataRefer);
        $this->componentData[$componentId] = $temp;
    }

    /**
     * 标记组件所属楼层
     *
     * @param array $dataRefer
     * @param int   $floor
     */
    private function markComponentFloor(array &$dataRefer, int $floor)
    {
        if (!empty($dataRefer)) {
            if (!empty($dataRefer['list']) && is_array($dataRefer['list'])) {
                foreach ($dataRefer['list'] as $key => &$valueRefer) {
                    $valueRefer['ad_id'] = '';
                    $valueRefer['ad_name'] = '';
                    $valueRefer['floor_id'] = "{$floor}-" . ($key + 1);
                }
            } else {
                $dataRefer['ad_id'] = '';
                $dataRefer['ad_name'] = '';
                $dataRefer['floor_id'] = "{$floor}-1";
            }
        }
    }

    /**
     * 转换为APP对应的像素大小
     *
     * @param int $value
     * @return float
     */
    private function getRound($value)
    {
        return round($value / 2, 2);
    }
}

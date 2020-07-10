<?php
namespace App\Services\Site\Zaful;

use App\Services\Site\IBaseDataTransformer;

/**
 * zaful 站点接口返回数据转换器
 *
 * @author tianhaishen
 */
class ZafulBaseDataTransformer implements IBaseDataTransformer
{

    /**
     * @inheritdoc
     */
    public function getCategoryAttrsById($baseDataProvider, $lang, $categoryId)
    {
        $allCatInfo = $baseDataProvider->getAllCategory($lang);
        if (is_array($allCatInfo) && isset($allCatInfo[$categoryId])) {
            $templateId = $allCatInfo[$categoryId]['template_id'] ?? 0;
            if ((int)$templateId > 0) {
                $templateAttrs = $baseDataProvider->getCategoryAttributes($lang, ['templateId' => $templateId]);
                return $baseDataProvider->getTransformer()->transGoodsAttr($templateAttrs);
            }
        }
        return [];
    }

    /**
     * @inheritdoc
     */
    public function transGoodsAttr(&$apiResultRefer)
    {
        $attrs = [];
        if (is_array($apiResultRefer) && !empty($apiResultRefer)) {
            foreach ($apiResultRefer as &$attrInfoRefer) {
                $attrs[] = [
                    'title' => $attrInfoRefer['search_attr_name'],
                    'name' => $attrInfoRefer['search_attr_name_en'],
                    'type' => $attrInfoRefer['style_type'],
                ];
            }
            unset($attrInfoRefer);
        }
        return $attrs;
    }

    /**
     * @inheritdoc
     */
    public function transGoodsPrice(&$apiResultRefer)
    {
        $result = [];
        foreach ($apiResultRefer as $goodsSku => $priceInfo) {
            $result[$goodsSku] = [
                'price' => $priceInfo['price'],
                'promotions' => $priceInfo['promotions'] ?? [],
            ];
        }
        return $result;
    }
}

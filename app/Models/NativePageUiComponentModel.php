<?php


namespace App\Models;


use Illuminate\Database\Query\Expression;

/**
 * 原生页组件数据模型
 *
 * Class NativePageUiComponentModel
 *
 * @package App\Models
 */
class NativePageUiComponentModel extends BaseModel
{
    /** @var string 表名 */
    const TABLE_NAME = 'native_page_ui_component';

    public function __construct(array $attributes = [])
    {
        $this->setTable(self::TABLE_NAME);
        parent::__construct($attributes);
    }

    /**
     * 取组件内容
     *
     * @param int    $pageId
     * @param string $lang
     * @param        $componentId
     *
     * @return array
     */
    public static function getNativePageUiData(int $pageId, string $lang, array $componentId)
    {
        $result = self::query()->from(self::TABLE_NAME . ' AS pu')
            ->select([
                'pu.page_id',
                'pu.component_id',
                'pu.component_key',
                'pu.style_data',
                'pu.sku_data',
                'pu.setting_data',
                'pu.tpl_id',
                'pu.tpl_title',
                'pu.tpl_name',
                'u.name as component_name',
                'u.need_navigate'
            ])
            ->leftJoin( 'ui_component AS u', 'pu.component_key', '=', 'u.component_key')
            ->where(['pu.page_id' => $pageId, 'pu.lang' => $lang])
            ->orderBy(new Expression("FIND_IN_SET(pu.component_id, '" . implode(',', $componentId) . "')"))
            ->get()
            ->toArray();

        if (!empty($result) && is_array($result)) {
            foreach ($result as &$value) {
                $value = array_map(function ($item) {
                    if (is_string($item) && json_decode($item, true)) {
                        $item = json_decode($item, true);
                    }

                    return $item;
                }, $value);
            }
        }

        return !empty($result) ? $result : [];
    }
}

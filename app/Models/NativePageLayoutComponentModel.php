<?php


namespace App\Models;


class NativePageLayoutComponentModel extends BaseModel
{

    public function __construct(array $attributes = [])
    {
        $this->setTable('native_page_layout_component');
        parent::__construct($attributes);
    }

    /**
     * 获取页面组件数据
     *
     * @param int    $pageId
     * @param string $lang
     *
     * @return array
     */
    public static function getNativePageLayouts(int $pageId, string $lang)
    {
        $result = self::query()->select('data')->where(['page_id' => $pageId, 'lang' => $lang])->first();
        return !empty($result->data) ? json_decode($result->data, true) : [];
    }
}

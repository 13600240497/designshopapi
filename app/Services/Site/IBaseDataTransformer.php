<?php
namespace App\Services\Site;

use App\Exceptions\ApiRequestException;

/**
 * 站点基础数据转换器
 *
 * @author tianhaishen
 */
interface IBaseDataTransformer
{
    /** @var int 属性显示类型 - 普通 */
    const ATTR_SHOW_TYPE_NORMAL = 1;

    /** @var int 属性显示类型 - 颜色 */
    const ATTR_SHOW_TYPE_COLOR = 2;

    /** @var int 属性显示类型 - 价格 */
    const ATTR_SHOW_TYPE_PRICE = 3;


    /**
     * 根据分类ID获取 属性组件 属性列表
     *
     * @param IBaseDataProvider $baseDataProvider 基础数据对象
     * @param string $lang 语言简码，如 en/fr
     * @param int $categoryId 分类ID
     * @return array
     *
     * - 格式:
     * - title 属性搜索值,根据多语言变化
     * - name 属性英文名称，用于对应不同ES搜索项
     * - type 前端根据这个值来使用不的展现方式(1: 常规显示, 2:颜色显示 3: 价格)
     *
     * @throws ApiRequestException
     */
    public function getCategoryAttrsById($baseDataProvider, $lang, $categoryId);

    /**
     * 转换商品价格数据
     *
     * 返回格式:
     * sku下标 => 当前生效销售价格
     *
     * @param array $apiResultRefer 价格接口返回结果
     * @return array
     */
    public function transGoodsPrice(&$apiResultRefer);

    /**
     * 转换属性
     * 格式：
     * - title 线上名称（多语言）
     * - name 名称
     * - type 类型
     *
     * @param array $apiResultRefer 属性接口返回结果
     * @return mixed
     */
    public function transGoodsAttr(&$apiResultRefer);
}

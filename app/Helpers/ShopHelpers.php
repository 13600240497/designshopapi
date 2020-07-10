<?php
namespace App\Helpers;

/**
 * 商城相关函数
 *
 * @author TianHaisen
 */
class ShopHelpers
{
    /**
     * 计算商品折扣
     *
     * @param float $originalPrice 原价
     * @param float $specialPrice 优惠价
     * @return int
     */
    public static function getGoodsDiscount($originalPrice, $specialPrice)
    {
        return (int)(($specialPrice/$originalPrice) * 100);
    }

    /**
     * 获取商品价格
     *
     * @param mixed $price
     * @return float
     */
    public static function getGoodsPrice($price)
    {
        return round(floatval($price), 2);
    }

    /**
     * 获取商品价格(字符串)
     *
     * @param mixed $price
     * @return string
     */
    public static function getGoodsPriceString($price)
    {
        return sprintf('%.2f', $price);
    }
}

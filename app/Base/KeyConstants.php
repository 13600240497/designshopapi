<?php
namespace App\Base;

/**
 * API 标准数据格式数组键名相关常量定义
 *
 * @author TianHaisen
 */
interface KeyConstants
{

    /** @var string 商品列表 */
    const GOODS_LIST = 'goods_list';

    /** @var string 秒杀信息 */
    const TSK_INFO = 'tsk_info';


    /** @var string 分页 */
    const PAGINATION = 'pagination';

    /** @var string 分页 - 当前页码 */
    const PAGE_NUM = 'page_num';

    /** @var string 分页 - 分页大小 */
    const PAGE_SIZE = 'page_size';

    /** @var string 分页 - 总记录数 */
    const TOTAL_COUNT = 'total_count';
}

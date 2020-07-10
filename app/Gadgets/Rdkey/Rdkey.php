<?php
/**
 * Redis key的相关管理
 */

namespace App\Gadgets\Rdkey;

use App\Gadgets\Rdkey\BussKey\PromotionKey;

/**
 * Class Rdkey
 *
 * @property BussKey\PromotionKey   $rdkPromotion
 * @property BussKey\ApiKey         $api
 * @property BussKey\WebKey         $web
 *
 * @package App\Gadgets\Rdkey
 */
class Rdkey
{

    /**
     * 已初始化的Rdkey实例对象池
     *
     * @var array
     */
    private $alreadyInstance = [];

    /**
     * 基于传过来的模块，获取对应模块Rdkey的实例
     *
     * @param string $rdkeyModel rdkPromotion
     *
     * @throws RdkeyException
     *
     * @return PromotionKey
     */
    public function __get($rdkeyModel = '')
    {
        $modelName = strtr($rdkeyModel, ['rdk'=>'']);
        $rdKeyClass = sprintf("%s\BussKey\%sKey", __NAMESPACE__, ucfirst($modelName));

        if (array_key_exists($rdKeyClass, $this->alreadyInstance)) {
            return $this->alreadyInstance[$rdKeyClass];
        } else {
            if (class_exists($rdKeyClass) == false) {
                throw new RdkeyException(sprintf('RDKEY ERROR : KEY MODEL NAME %s NOT EXIST !!', $rdKeyClass));
            }
        }

        return $this->alreadyInstance[$rdKeyClass] = new $rdKeyClass();
    }
}

<?php
namespace Tests\Unit\Site\Zaful;

use Tests\AbstractTestCase;
use App\Helpers\BeanHelpers;
use App\Exceptions\ApiRequestException;
use App\Services\Site\IBaseDataProvider;

/**
 * zaful站点基础数据接口测试
 *
 * @author tianhaishen
 */
class ZafulBaseDataProviderTest extends AbstractTestCase
{
    /** @var string 商品SKU列表 */
    private $goodsSkuString = '268719102';

    /** @var IBaseDataProvider 基础数据提供者对象 */
    private $dataProvider;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->dataProvider = BeanHelpers::getBaseDataProvider('zf-pc');
    }

    /**
     * 测试价格接口是否正常
     *
     * @return array
     */
    public function testGetGoodsPriceApiRequest()
    {
        try {
            $result = $this->dataProvider->getGoodsPrice($this->goodsSkuString, ['pipeline' => 'ZF']);
            $this->assertNotEmpty($result);
            return $result;
        } catch (ApiRequestException $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     * 测试价格接口是营销信息
     */
    public function testGoodsPromotion()
    {
        try {
            $goodsSkuString = '279791106';
            $result = $this->dataProvider->getGoodsPrice($goodsSkuString, ['pipeline' => 'ZF']);
            print_r($result);
            $this->assertNotEmpty($result);
        } catch (ApiRequestException $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     * 测试价格接口返回
     *
     * @param array $result
     * @depends testGetGoodsPriceApiRequest
     */
    public function testGoodsPrice(array $result)
    {
        $priceList = $this->dataProvider->getTransformer()->transGoodsPrice($result);
        $this->assertTrue(array_key_exists($this->goodsSkuString, $priceList));
    }

    /**
     * 测试分类接口是否正常，不走缓存
     *
     * @return array
     */
    public function testGetAllCategoryApiRequest()
    {
        try {
            $result = $this->invokeMethod($this->dataProvider, 'callGetAllCategoryApi', ['en']);
            $this->assertNotEmpty($result);
            return $result;
        } catch (\Throwable $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     * 测试分类属性接口是否正常，不走缓存
     *
     * @return array
     */
    public function testCategoryAttributesApiRequest()
    {
        try {
            $result = $this->invokeMethod($this->dataProvider, 'callGetTemplateInfoApi', ['en', 3]);
            $this->assertNotEmpty($result);
            return $result;
        } catch (\Throwable $e) {
            $this->fail($e->getMessage());
        }
    }

}

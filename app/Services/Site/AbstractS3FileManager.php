<?php
namespace App\Services\Site;

use App\Services\AbstractGuzzleHttp;
use GuzzleHttp\Exception\RequestException;

/**
 * S3文件管理抽象类
 *
 * @author tianhaishen
 */
abstract class AbstractS3FileManager extends AbstractGuzzleHttp
{
    /** @var string 网站简码 */
    protected $siteCode;

    /** @var string 语言简码 */
    protected $lang;

    /** @var string 国家站编码 */
    protected $pipeline;

    /**
     * 构造函数
     *
     * @param string $siteCode 网站简码
     * @param string $lang 语言简码
     * @param string $pipeline 国家站编码
     */
    public function __construct(string $siteCode, string $lang, string $pipeline)
    {
        $this->siteCode = $siteCode;
        $this->lang = $lang;
        $this->pipeline = $pipeline;
    }

    /**
     * 获取文件内容
     *
     * @param string $url s3路径完整url
     * @return string
     * @throws RequestException
     */
    public function getFileBody($url)
    {
        $options = $this->getBaseOptions();
        $response = $this->getClient()->get($url, $options);
        return $response->getBody()->getContents();
    }

    /**
     * 获取非静态文件(json和html)s3访问域名
     *
     * @return string 访问域名,如：http://geshop.s3.amazonaws.com
     */
    public function getNonStaticS3Domain()
    {
        return config('filesystems.disks.s3.url');
    }

    /**
     * 获取json数据文件的S3的完整URL
     *
     * @param string $filename 文件名称, 如: api-async-data-40085.json
     * @return string 文件s3完整URL,如: http://geshop.s3.amazonaws.com/publish/m.zaful.com/en/api-async-data-40085.json
     */
    public function getJsonDataFileUrl($filename)
    {
        $domain = $this->getNonStaticS3Domain();
        return $domain . $this->getJsonDataFileUri($filename);
    }

    /**
     * 获取活动生成页面html文件的S3的完整URI,不包含域名部分
     *
     * @param string $filename 文件名称, 如: 5th-anniversary-carnival.html
     * @return string 文件s3完整URL,如: http://geshop.s3.amazonaws.com/publish/www.zaful.com/en/5th-anniversary-carnival.html
     */
    public function getHtmlFileUrl($filename)
    {
        $domain = $this->getNonStaticS3Domain();
        return $domain . $this->getHtmlFileUri($filename);
    }

    /**
     * 获取json数据文件的S3的完整URI,不包含域名部分
     *
     * @param string $filename 文件名称, 如: api-async-data-40085.json
     * @return string 文件s3路径uri,如: /publish/m.zaful.com/en/api-async-data-40085.json
     */
    public abstract function getJsonDataFileUri($filename);

    /**
     * 获取活动生成页面html文件的S3的完整URI,不包含域名部分
     *
     * @param string $filename 文件名称, 如: 5th-anniversary-carnival.html
     * @return string 文件s3路径uri,如: /publish/www.zaful.com/en/5th-anniversary-carnival.html
     */
    public abstract function getHtmlFileUri($filename);
}

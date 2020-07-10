<?php
namespace App\Services\Site\Zaful;

use App\Helpers\AppHelpers;
use App\Services\Site\AbstractS3FileManager;

/**
 * Zaful S3文件管理
 *
 * @author tianhaishen
 */
class ZafulS3FileManager extends AbstractS3FileManager
{

    /**
     * @inheritDoc
     */
    public function getJsonDataFileUri($filename)
    {
        return $this->getNonStaticFileUri($filename);
    }

    /**
     * @inheritDoc
     */
    public function getHtmlFileUri($filename)
    {
        return $this->getNonStaticFileUri($filename);
    }

    /**
     * 获取非静态文件(json和html)s3路径uri
     *
     * @param string $filename 文件名称
     * @return string 文件s3路径uri,如: /publish/www.zaful.com/en/5th-anniversary-carnival.html
     */
    private function getNonStaticFileUri($filename)
    {
        $keyFormat = 'publish.%s.s3PublishPath.%s.%s';
        $path = config(sprintf($keyFormat, $this->siteCode, $this->pipeline, $this->lang));
        if (!empty($path)) {
            $path = '/' . trim($path, '/');
            if (AppHelpers::isStagingEnv()) {
                $path = $path . '/release';
            }
            return $path . '/' . $filename;
        }
        return '';
    }
}

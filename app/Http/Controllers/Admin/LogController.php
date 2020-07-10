<?php
namespace App\Http\Controllers\Admin;

use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Http\Controllers\AbstractWebController;

/**
 * 线上日志查看
 *
 * @author TianHaisen
 */
class LogController extends AbstractWebController
{
    /** @var int 最大可查看日志大小 */
    public $maxSize = 5632*20; // kb, 5.5M

    /**
     * 日志文件列表
     *
     * @return Response
     */
    public function logList()
    {
        $result = [];
        $rootPath = realpath(base_path('storage/logs'));
        $iterator = new \FilesystemIterator($rootPath);
        $len      = strlen($rootPath) + 1;
        foreach($iterator as $item) {
            $file = substr($item, $len);
            if ($item->isFile()) {
                $result[$file] = [
                    'file'  => substr($item, $len),
                    'size'  => $item->getSize(),
                    'time'  => date('Y-m-d H:i:s', $item->getMTime()),
                ];
            } else {
                $result[$file] = [
                    'file' => substr($item, $len),
                    'size' => '--',
                    'time' => '--',
                ];
            }
        }
        uasort($result, [$this, 'sortFile']);

        if (\is_array($result)) {
            array_walk($result, function (&$item) {
                $item['file'] = sprintf(
                    '<a href="%s" target="_blank">%s</a>',
                    'view?file='. $item['file'],
                    $item['file']
                );
            });
            $result = var_export($result, true);
        }

        return response('<pre>' . $result . '</pre>');
    }

    /**
     * 查看日志内容
     *
     * @return Response
     */
    public function view()
    {
        $filename = $params = request()->get('file');
        $rootPath = realpath(base_path('storage/logs'));
        if (is_file($file = $rootPath . '/' . $filename)) {
            if (filesize($file) > $this->maxSize * 1024) {
                $content = '文件大小超出限制';
            } else {
                $content = htmlspecialchars(file_get_contents($file), ENT_QUOTES|ENT_IGNORE);
                $content = explode("\n", $content);
                $content = array_slice($content, -500, -1);
                $content = '<pre>' . join("\n", array_map('trim', $content)) . '</pre>';
            }
        } else {
            $content = '日志文件"' . $file . '"不存在';
        }

        return response($content);
    }

    /**
     * 日志下载
     *
     * @return BinaryFileResponse|Response
     */
    public function download()
    {
        $filename = $params = request()->get('file');
        $file = realpath(base_path('storage')) .'/logs/'. $filename;
        if (!is_file($file)) {
            return response('file is not found!');
        }

        return response()->download($file, $filename);
    }

    /**
     * 按时间倒序文件
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    private function sortFile($a, $b)
    {
        return $a['time'] > $b['time'] ? -1 : 1;
    }
}

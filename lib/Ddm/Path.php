<?php

namespace Ddm;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Path
{

    /** @var string */
    protected string $base = '/';

    /**
     * @param string $base 作为相对路径的起点
     */
    public function __construct(string $base)
    {
        $this->base = $base;
    }

    /**
     * 拼接路径。例：
     *  - join('/www', 'web', '/test', './foo')          => /www/web/test/foo
     *  - join('/www', 'web', '/test', './foo', '../app') => /www/web/test/app
     *  - Windows 下 join('C:\www', 'web', '/test', './foo') => C:\www\web\test\foo
     *
     * @return string
     * @throws \RuntimeException
     */
    public function join(): string
    {
        $args = func_get_args();
        $result = null;
        $first = true;
        foreach ($args as $arg) {
            if (!is_string($arg)) {
                $arg = (string) $arg;
            }
            $arg = trim($arg);
            if ($arg === '') {
                continue;
            }
            // 展开 ~ 前缀
            $arg = $this->expandTilde($arg);
            if ($first) {
                $first = false;
                if ($this->isAbsolutePath($arg)) {
                    $result = $arg;
                } else {
                    $result = $this->base . '/' . $arg;
                }
            } else {
                // 后续一律当作相对片段追加
                $result = $result . '/' . $arg;
            }
        }
        if ($result === null) {
            $result = $this->base;
        }
        return $this->normalize($result);
    }

    /**
     * 跨平台判断是否为绝对路径。
     *
     * @param string $path
     * @return bool
     */
    public function isAbsolutePath(string $path): bool
    {
        if ($path === '') {
            return false;
        }
        // Windows 盘符：C:\ 或 C:/ 开头
        // 注意 C:foo（没有分隔符）不算绝对路径
        if (preg_match('#^[A-Za-z]:[\\\\/]#', $path)) {
            return true;
        }
        // 以 / 或 \ 开头：
        //  - Unix: /foo 是绝对路径
        //  - Windows: \foo、/foo 表示当前盘符根目录
        //  - Windows UNC: \\server\share、//server/share
        return $path[0] === '/' || $path[0] === '\\';
    }

    /**
     * 获取当前 PHP 进程用户的HOME目录。
     *
     * @return string
     * @throws \RuntimeException
     */
    public function getHomeDirectory(): string
    {
        // 类 Unix：HOME
        $home = getenv('HOME');
        if (is_string($home) && $home !== '') {
            return $home;
        }
        // Windows：USERPROFILE
        $home = getenv('USERPROFILE');
        if (is_string($home) && $home !== '') {
            return $home;
        }
        // Windows：HOMEDRIVE + HOMEPATH
        $drive = getenv('HOMEDRIVE');
        $subpath = getenv('HOMEPATH');
        if (is_string($drive) && $drive !== '' && is_string($subpath) && $subpath !== '') {
            return $drive . $subpath;
        }
        // POSIX：按当前有效用户查 passwd
        if (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
            $info = @posix_getpwuid(posix_geteuid());
            if (is_array($info) && !empty($info['dir'])) {
                return $info['dir'];
            }
        }
        throw new \RuntimeException('无法确定当前用户的HOME目录');
    }

    /**
     * 规范化路径：统一分隔符、合并多余分隔符、去掉 . 、处理 ..。
     *
     * @param string $path
     * @return string
     */
    protected function normalize(string $path): string
    {
        if ($path === '') {
            return '';
        }
        // 先统一为 /
        $path = str_replace('\\', '/', $path);
        // 处理盘符前缀，如 C:
        $prefix = '';
        if (preg_match('#^([A-Za-z]:)#', $path, $m)) {
            $prefix = $m[1];
            $path = substr($path, strlen($prefix));
        }
        // 是否是根路径
        $isRoot = isset($path[0]) && $path[0] === '/';
        // 拆分、处理 . 和 ..
        $segments = explode('/', $path);
        $stack = [];
        foreach ($segments as $seg) {
            if ($seg === '' || $seg === '.') {
                continue;
            }
            if ($seg === '..') {
                if (!empty($stack)) {
                    array_pop($stack);
                }
                // 栈为空时，绝对路径的 .. 不产生效果；相对路径的 .. 也忽略
                continue;
            }
            $stack[] = $seg;
        }
        $body = implode('/', $stack);
        if ($prefix !== '') {
            // Windows：C: 前缀，至少保留 C:\
            $result = $body === '' ? $prefix . '/' : $prefix . '/' . $body;
        } elseif ($isRoot) {
            $result = '/' . $body;
        } else {
            $result = $body;
        }
        // 统一为系统分隔符
        if (DIRECTORY_SEPARATOR === '\\') {
            $result = str_replace('/', '\\', $result);
        }
        return $result;
    }

    /**
     * 展开 ~、~/xxx、~\xxx 为当前用户HOME目录。
     *
     * @param string $path
     * @return string
     * @throws \RuntimeException
     */
    protected function expandTilde(string $path): string
    {
        if ($path === '~') {
            return $this->getHomeDirectory();
        }
        if (strlen($path) >= 2 && $path[0] === '~' && ($path[1] === '/' || $path[1] === '\\')) {
            $home = $this->getHomeDirectory();
            $rest = substr($path, 2);
            return $home . '/' . $rest;
        }
        return $path;
    }
}

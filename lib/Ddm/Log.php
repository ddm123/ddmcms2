<?php

namespace Ddm;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Log
{

    private static ?string $currentUr = null;

    /**
     * 保存日志
     *
     * @param mixed ...$message
     * @return int|false
     */
    public static function save(...$message)
    {
        $fileName = 'system-'.date('Y-m').'.log';
        return self::saveTo($fileName, ...$message);
    }

    /**
     * 保存日志到指定文件
     *
     * @param string $fileName
     * @param mixed ...$message
     * @return int|false
     * @throws \Exception
     */
    public static function saveTo(string $fileName, ...$message)
    {
        if (str_contains($fileName, '..')) {
            throw new \Exception('Invalid file name: '.$fileName);
        }
        if (!$message) {
            return 0;
        }

        $k = array_key_first($message);
        $str = '['.date('c').'] '.self::varToString($message[$k]);
        unset($message[$k]);

        if ($message) {
            foreach ($message as $v) {
                $str .= ' '.self::varToString($v);
            }
        }

        // 如果是斜杠开头
        if ($fileName[0] === '/' || $fileName[0] === '\\') {
            $file = SITE_ROOT.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'logs'.$fileName;
        } else {
            $file = SITE_ROOT.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'logs'.DIRECTORY_SEPARATOR.$fileName;
        }

        if (file_exists($file)) {
            $putSize = file_put_contents($file, $str.\PHP_EOL, \FILE_APPEND | \LOCK_EX);
            $putSize and self::checkFileSize($file); // 检查文件大小
        } else {
            $dir = dirname($file);
            if (!file_exists($dir)) mkdir($dir, 0775, true);
            $putSize = file_put_contents($file, $str.\PHP_EOL, \FILE_APPEND | \LOCK_EX);
            chmod($file, 0664);
        }
        return $putSize;
    }

    /**
     * 文件调用堆栈
     *
     * @param string|bool|null $fileName
     * @param bool $incUrl
     * @return string|null
     */
    public static function traces($fileName = null, bool $incUrl = false)
    {
        $output = '';
        foreach (debug_backtrace() as $key => $item) {
            if ($item['function'] == 'include' || $item['function'] == 'include_once' || $item['function'] == 'require_once' || $item['function'] == 'require') {
                $output .= '#'.$key.' '.$item['function'].'('.$item['args'][0].') called at ['.$item['file'].':'.$item['line'].']'.\PHP_EOL;
            } else {
                if ($args = isset($item['args']) ? $item['args'] : []) {
                    $args = array_map(function($v){
                        if(is_object($v))$v = get_class($v);
                        else if(is_array($v))$v = 'array('.count($v).')';
                        else if(is_string($v))$v = '\''.$v.'\'';
                        else if(is_bool($v))$v = $v ? 'true' : 'false';
                        else if($v===null)$v = 'null';
                        return $v;
                    },$args);
                }
                $class = isset($item['object']) ? get_class($item['object']) : '';
                $output .= '#'.$key.' '.(isset($item['class']) ? $item['class'].($class==$item['class'] ? '' : '('.$class.')').$item['type'] : '').$item['function'].'('.implode(',',$args).') called at ['.(isset($item['file']) ? $item['file'] : 'Unknown file').':'.(isset($item['line']) ? $item['line'] : 'Unknown file').']'.\PHP_EOL;
            }
        }

        if ($incUrl) {
            $output .= 'URL: '.self::getCurrentUrl(true).\PHP_EOL;
        }
        if ($fileName===true) {
            return $output;
        }
        if ($fileName===false) {
            echo $output;
        } else {
            $fileName ? self::saveTo($fileName, $output) : self::save($output);
        }
    }

    /**
     * @return string
     */
    public static function getCurrentUrl($appendRequestMethod = false): string
    {
        if (self::$currentUr === null) {
            $url = '';
            if (\PHP_SAPI === 'cli') {
                $url .= 'php -f ';
                if (!empty($_SERVER['PWD']) && strpos($_SERVER['argv'][0], $_SERVER['PWD'])!==0) $url .= $_SERVER['PWD'].\DIRECTORY_SEPARATOR;
                $url .= implode(' ', $_SERVER['argv']);
            } else {
                $url .= !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!='off' ? 'https://' : 'http://';
                if(isset($_SERVER['HTTP_HOST']))$url .= $_SERVER['HTTP_HOST'];
                if(isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT']!='80' && $_SERVER['SERVER_PORT']!='443')$url .= ':'.$_SERVER['SERVER_PORT'];
                if(isset($_SERVER['REQUEST_URI']))$url .= $_SERVER['REQUEST_URI'];
                //if(!empty($_SERVER['QUERY_STRING']))$url .= '?'.$_SERVER['QUERY_STRING'];
            }
            self::$currentUr = $url;
        }

        return $appendRequestMethod && isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'].' '.self::$currentUr : self::$currentUr;
    }

    public static function registerPHPError(): void
    {
        register_shutdown_function(function() {
            $error = error_get_last();
            if ($error) {
                self::saveTo('php-error.log', $error);
            }
        });
    }

    private static function filterObject(array $var): array
    {
        foreach ($var as $k => $v) {
            if (is_array($v)) $var[$k] = self::filterObject($v);
            else if (is_object($v)) $var[$k] = 'object('.get_class($v).')';
        }
        return $var;
    }

    private static function varToString(mixed $var): mixed
    {
        if(is_object($var))$var = 'object('.get_class($var).')';
        else if(is_array($var))$var = json_encode(self::filterObject($var), \JSON_UNESCAPED_UNICODE|\JSON_UNESCAPED_SLASHES|\JSON_PRETTY_PRINT);
        else if(is_bool($var))$var = $var ? 'bool(true)' : 'bool(false)';
        else if($var===null)$var = 'NULL';
        else if($var==='')$var = 'string(0) ""';
        return $var;
    }

    private static function checkFileSize(string $file): bool
    {
        // 检查文件是否已超过10MB，超过则备份重新创建
        if (@filesize($file) >= 10 * 1024 * 1024) {
            self::backupLogFiles($file);
            return rename($file, $file.'1.bak');
        }

        return false;
    }

    private static function backupLogFiles(string $file): bool
    {
        // 检查文件数量是否已超过10个，超过则删除最早的文件
        $files = glob($file.'*.bak');
        if (in_array($file.'1.bak', $files)) {
            $lastKey = array_key_last($files);

            for ($i = 8; $i <= $lastKey; $i++) {
                unlink($files[$i]);
            }
            for ($j = 7; $j >= 0; $j--) {
                isset($files[$j]) and rename($files[$j], $file.($j + 2).'.bak');
            }
            return true;
        }
        return false;
    }
}

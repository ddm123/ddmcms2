<?php

namespace Ddm;

use Ddm;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class StringHelper
{

    protected static mixed $_instance = null;

    public function __construct()
    {
        //;
    }

    /**
     * 使用单例模式
     * @return \Ddm\StringHelper
     */
    public static function singleton(): StringHelper
    {
        return self::$_instance === null ? self::$_instance = new StringHelper() : self::$_instance;
    }

    /**
     * Escape html entities
     *
     * @param mixed $data
     * @param array $allowedTags
     * @return mixed
     */
    public function escapeHtml(mixed $data, ?array $allowedTags = null): mixed
    {
        if (is_array($data)) {
            $result = [];
            foreach ($data as $item) {
                $result[] = $this->escapeHtml($item);
            }
        } elseif ($data) {
            if (!empty($allowedTags)) {
                $allowed = implode('|', $allowedTags);
                $result = preg_replace('/<([\/\s\r\n]*)(' . $allowed . ')([\/\s\r\n]*)>/si', '##$1$2$3##', $data);
                $result = htmlspecialchars($result, ENT_COMPAT, 'UTF-8', true);
                $result = preg_replace('/##([\/\s\r\n]*)(' . $allowed . ')([\/\s\r\n]*)##/si', '<$1$2$3>', $result);
            } else {
                $result = htmlspecialchars($data, ENT_COMPAT, 'UTF-8', true);
            }
        } else {
            $result = $data;
        }
        return $result;
    }

    /**
     * @param string $text
     * @param string $escapeHtml
     * @param array $allowedTags
     * @return string
     */
    public function textToHtml(string $text, bool $escapeHtml = true, ?array $allowedTags = null): string
    {
        if ($escapeHtml) {
            $text = $this->escapeHtml($text, $allowedTags);
        }
        return str_replace('  ', '&nbsp; ', nl2br($text));
    }

    public function escapePhpTag(mixed $html): mixed
    {
        if ($html) {
            $html = preg_replace(['/<([\?\%])/', '/([\?\%])>/'], ['&lt;\1', '\1&gt;'], $html);
        }
        return $html;
    }

    /**
     *  @param string $string
     *  @return string
     */
    public function base64Encode(string $string): string
    {
        return strtr(base64_encode($string), '+=/', '-_,');
    }

    /**
     *  @param string $string
     *  @return string
     */
    public function base64Decode(string $string): string
    {
        return base64_decode(strtr($string, '-_,', '+=/'));
    }

    /**
     * @param float $size
     * @return string
     */
    public function formatBytes(float $size): string
    {
        $count = 0;
        $format = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        while ($size / 1024 > 1 && $count++ < 8) {
            $size = $size / 1024;
        }
        return number_format($size, 2, '.', ',') . ' ' . $format[$count];
    }

    /**
     * @param float $number
     * @return string
     */
    public function currency(float $number): string
    {
        if ($number === null || $number === false) {
            return $number;
        }
        $decimals = (int) Ddm::getConfig()->getConfigValue('system/currency/precision') or $decimals = 2;
        $decPoint = Ddm::getConfig()->getConfigValue('system/currency/dec_point') or $decPoint = '.';
        $thousandsSep = Ddm::getConfig()->getConfigValue('system/currency/thousands_sep') or $thousandsSep = ',';
        $currencySymbol = Ddm::getConfig()->getConfigValue('system/currency/currency_symbol') or $currencySymbol = '¥';
        $number = number_format($number, $decimals, $decPoint, $thousandsSep);
        return Ddm::getConfig()->getConfigValue('system/currency/currency_seat') ? $currencySymbol . $number : $number . $currencySymbol;
    }

    /**
     * 截取一段指定字符数的字符, 该方法优先考虑长度对齐问题, 所以返回字符数可能会和指定的字符数不相等
     * @param string $str
     * @param int $length
     * @param string $etc
     * @param bool $stripTags
     * @return string
     */
    public function cutString(string $str, int $length, string $etc = '...', bool $stripTags = false): string
    {
        $length = (int) $length;
        if ($length < 1 || (string) $str === '') {
            return '';
        }
        if ($stripTags) {
            //去掉样式标签, 因为PHP的strip_tags并没有去掉
            $str = preg_replace(["/<style[^>]*?>.*?<\\/style>/si", '/\s{2,}/'], ['', ' '], strip_tags($str));
        }
        $result = '';
        $len = strlen($str);
        if ($length >= $len) {
            return $str;
        }
        for ($i = $n = 0; $n < $length && $i <= $len;) {
            if (!isset($str[$i])) {
                break;
            }
            $a = ord($str[$i]);
            if ($a >= 224) {
                $result .= substr($str, $i, 3);
                //根据UTF-8编码规范，将3个连续的字符计为单个字符
                $i += 3;
                //实际Byte计为3
                $n++;
                //字串长度计1
            } elseif ($a >= 192) {
                //如果ASCII位高与192，
                $result .= substr($str, $i, 2);
                //根据UTF-8编码规范，将2个连续的字符计为单个字符
                $i += 2;
                //实际Byte计为2
                $n++;
                //字串长度计1
            } elseif ($a >= 65 && $a <= 90) {
                //如果是大写字母，
                $result .= substr($str, $i, 1);
                $i++;
                //实际的Byte数仍计1个
                $n += 0.58;
                //但考虑整体美观，大写字母计成一个高位字符
            } else {
                //其他情况下，包括小写字母和半角标点符号，
                $result .= substr($str, $i, 1);
                $i++;
                //实际的Byte数计1个
                $n += 0.48;
                //小写字母和半角标点等与半个高位字符宽...
            }
        }
        //超过长度时在尾处加上省略号
        return $len > $i ? $result . $etc : $result;
    }

    /**
     * 截取一段指定字符数的HTML, 该方法不破坏HTML代码, 不将HTML代码计算到字符数中
     * @param string $html
     * @param int $length
     * @param string $etc
     * @return string
     */
    public function cutHtml(string $html, int $length, string $etc = '...'): string
    {
        $len = $this->_getCutHtmlLength($html, $length);
        if ($len <= $length) {
            return $html;
        }
        $html = substr($html, 0, $len) . $etc;
        preg_match_all('/<([A-Za-z]+)[^>]*>/', $html, $matchesBegin);
        //匹配出全部开始标签
        //一个个的把结束标签补上
        if (isset($matchesBegin)) {
            preg_match_all('/<\/([A-Za-z]+)>/', $html, $matchesEnd);
            //匹配出全部结束标签
            $matchesEnd = $matchesEnd && isset($matchesEnd[1]) && $matchesEnd[1] ? array_map('strtolower', $matchesEnd[1]) : [];
            foreach (array_reverse($matchesBegin[1]) as $value) {
                $value = strtolower($value);
                if ($value == 'br' || $value == 'hr' || $value == 'img') {
                    continue;
                }
                if ($matchesEnd && false !== $i = array_search($value, $matchesEnd)) {
                    unset($matchesEnd[$i]);
                } else {
                    $html .= "</{$value}>";
                }
            }
        }
        return $html;
    }

    /**
     * 获取实际应该截取的字符数, 如果返回-1表示不需要截取
     * @param string $html
     * @param int $length
     * @return int
     */
    protected function _getCutHtmlLength(string $html, int $length): int
    {
        $l = strlen($html);
        if ($l <= $length) {
            return -1;
        }
        //$length--;//返回的字符数会比要取的字符数多1, 所以这里减1 (例如本来想取10个, 但返回了11)
        for ($i = 0, $j = 0, $c = $q = $q1 = $s = false; $i < $l && $j < $length; $i++) {
            $char = $html[$i];
            $nc = $i + 1 < $l ? strtolower($html[$i + 1]) : null;
            if ($c && $char == '"' && !$q1) {
                $q = !$q;
            }
            if ($c && $char == "'" && !$q) {
                $q1 = !$q1;
            }
            if ($nc && !$q && !$q1 && ($c || $char == '<' && ($nc >= 'a' && $nc <= 'z' || $nc == '/') || $c && $char == '>')) {
                if ($char == '<') {
                    $c = true;
                } elseif ($char == '>') {
                    $c = false;
                }
                continue;
            }
            if ($char == '&' && $nc >= 'a' && $nc <= 'z') {
                if (($e = substr($html, $i, 4)) == '&gt;' || $e == '&lt;') {
                    $j++;
                    $i += 3;
                    //上面有$i++, 所以这里只加3
                    continue;
                } elseif (($e = substr($html, $i, 5)) == '&amp;') {
                    $j++;
                    $i += 4;
                    //上面有$i++, 所以这里只加4
                    continue;
                } elseif (($e = substr($html, $i, 6)) == '&quot;' || $e == '&#039;' || $e == '&nbsp;') {
                    $j++;
                    $i += 5;
                    //上面有$i++, 所以这里只加5
                    continue;
                }
            }
            if (!$q && !$s) {
                $a = ord($char);
                if ($a >= 224) {
                    $i += 2;
                } elseif ($a >= 128) {
                    $i += 1;
                }
                //上面有$i++, 所以这里只加1
                $j++;
            }
            $s = $char == ' ' || $char == "\t" || $char == "\r" || $char == "\n";
        }
        return $i >= $l ? -1 : $i;
    }
}

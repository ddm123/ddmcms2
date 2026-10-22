<?php

namespace Core\Model;

use Ddm;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class DateTime extends \DateTime
{

    protected static mixed $_now = null;

    protected mixed $_timezone = null;

    protected mixed $_timezoneName = null;

    protected mixed $_defaultDateTimeZoneName = null;

    /**
     * @param string $time
     * @param DateTimeZone|null $timezone
     */
    public function __construct(string $time = 'now', ?\DateTimeZone $timezone = null)
    {
        if (!$timezone) {
            $timezone = $this->getDefaultDateTimeZoneName();
        }
        parent::__construct($time, $timezone instanceof \DateTimeZone ? $timezone : new \DateTimeZone($timezone));
    }

    /**
     * @return string
     */
    public static function now(): string
    {
        if (self::$_now === null) {
            $d = new \DateTime('now', $this->getTimezone());
            self::$_now = $d->format('Y-m-d H:i:s');
        }
        return self::$_now;
    }

    /**
     * @param int $unixtimestamp
     * @param string|DateTimeZone|null $timezone
     * @return \Core\Model\DateTime
     */
    public static function getFromUnixtimestamp(?int $unixtimestamp = null, string|\DateTimeZone|null $timezone = null): DateTime
    {
        $_datetime = $timezone ? new self('now', $timezone) : new self('now');
        if (is_numeric($unixtimestamp)) {
            $_datetime->setTimestamp((int) $unixtimestamp);
        }
        return $_datetime;
    }

    /**
     * @param string|DateTimeZone $timezone
     * @return $this
     */
    public function setTimezone(string|\DateTimeZone $timezone): static
    {
        $this->_timezone = $timezone instanceof \DateTimeZone ? $timezone : new \DateTimeZone($timezone);
        $this->_timezoneName = $this->_timezone->getName();
        parent::setTimezone($this->_timezone);
        return $this;
    }

    /**
     * @return DateTimeZone|false
     */
    public function getTimezone(): \DateTimeZone|false
    {
        if ($this->_timezone) {
            return $this->_timezone;
        }
        return $this->_timezone = parent::getTimezone();
    }

    /**
     * @param int $unixtimestamp
     * @return $this
     */
    public function setTimestamp(int $unixtimestamp): static
    {
        $unixtimestamp = (int) $unixtimestamp;
        if (method_exists('DateTime', 'setTimestamp')) {
            //如果是PHP5.3版本以上
            parent::setTimestamp($unixtimestamp);
        } else {
            $_date = new \DateTime(date('c', $unixtimestamp));
            $_date->setTimezone($this->getTimezone());
            $dateTime = explode('|', $_date->format('Y|n|j|G|i|s'));
            $this->setDate($dateTime[0], $dateTime[1], $dateTime[2]);
            $this->setTime($dateTime[3], (int) $dateTime[4], (int) $dateTime[5]);
        }
        return $this;
    }

    /**
     * @return int
     */
    public function getTimestamp(): int
    {
        return method_exists('DateTime', 'getTimestamp') ? parent::getTimestamp() : $this->format('U');
    }

    /**
     * @return string
     */
    public function getDefaultDateTimeZoneName(): string
    {
        if ($this->_defaultDateTimeZoneName === null) {
            $this->_defaultDateTimeZoneName = Ddm::getConfig()->getConfigValue('system/date/timezone') or $this->_defaultDateTimeZoneName = date_default_timezone_get() or $this->_defaultDateTimeZoneName = 'Etc/UTC';
        }
        return $this->_defaultDateTimeZoneName;
    }

    /**
     * @param string $format
     * @return string
     */
    public function date(?string $format = null): string
    {
        $format or $format = Ddm::getConfig()->getConfigValue('system/date/date_format') or $format = 'Y-m-d';
        return $this->format($format);
    }

    /**
     * @param string $format
     * @return string
     */
    public function dateTime(?string $format = null): string
    {
        $format or $format = Ddm::getConfig()->getConfigValue('system/date/datetime_format') or $format = 'Y-m-d H:i:s';
        return $this->format($format);
    }

    /**
     * 作用和strtotime()一样, 失败时返回false
     * @param string $time
     * @return int|false 返回时间戳
     */
    public function stringToTime(string $time = 'now', mixed $c = true): int|false
    {
        try {
            $_date = new \DateTime(strtr($time, '.', '-'), $this->getTimezone());
            //$_date->setTimezone($this->getTimezone());
            $timestamp = method_exists('DateTime', 'getTimestamp') ? $_date->getTimestamp() : $_date->format('U');
        } catch (\Exception $e) {
            $d = $c ? $this->_parseStringDate($time) : null;
            $timestamp = $d ? $this->stringToTime($d, false) : false;
        }
        return $timestamp;
    }

    /**
     * @param int $date
     * @return string
     */
    protected function _parseStringDate(int $date, mixed $y = 'Y', mixed $m = 'm'): string
    {
        $result = null;
        $now = new \DateTime('now', $this->getTimezone());
        if ((string) (int) $date === (string) $date) {
            $days = $now->format('t');
            if ($date <= $days) {
                $result = $now->format("{$y}-{$m}-{$date}");
            } elseif ($date < 130) {
                $d = explode('.', $date / 10);
                $result = $now->format("{$y}-{$d[0]}-" . (empty($d[1]) ? '1' : $d[1]));
            } elseif ($date > 200 && $date < 1300) {
                $d = explode('.', $date / 100);
                $result = $now->format("{$y}-{$d[0]}-" . (empty($d[1]) ? '1' : (strlen($d[1]) == 1 ? $d[1] * 10 : $d[1])));
            } elseif ($date > 1310 && $date < 991300) {
                $result = $this->_parseStringDate(substr($date, 2), substr($date, 0, 2));
            } elseif ($date > 190010) {
                $result = $this->_parseStringDate(substr($date, 4), substr($date, 0, 4));
            }
        } elseif (strpos($date, ':')) {
            $d = preg_split('/(\s|T)/i', $date, 2, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
            $result = $this->_parseStringDate($d[0]) . (isset($d[2]) ? $d[1] . $d[2] : '');
        } elseif ($d = preg_split('/[^\d]+/', $date, -1, PREG_SPLIT_NO_EMPTY)) {
            if (!isset($d[1])) {
                $result = (int) $d[0] ? $now->format("Y-m-{$d[0]}") : null;
            } elseif (!isset($d[2])) {
                $result = (int) $d[0] && (int) $d[1] ? $now->format("Y-{$d[0]}-{$d[1]}") : null;
            } elseif ((int) $d[0] && (int) $d[1] && (int) $d[2]) {
                $result = "{$d[0]}-{$d[1]}-{$d[2]}";
            }
        }
        return $result;
    }

    public function __toString(): string
    {
        return $this->format('c');
    }
}

<?php

namespace Admin\Block\Home;

use Core\Block\AbstractModel;
use Core\Model\Date\Timezone;
use Ddm;
use Ddm\Db;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Dashboard extends AbstractModel
{

    /**
     * @return string
     */
    public function getTimezoneName(): string
    {
        $timezoneName = Ddm::getHelper('core')->getDateTime()->getTimezone()->getName();
        $timezoneNameLabel = Timezone::singleton()->getTimezones($timezoneName);
        return $timezoneNameLabel ? $timezoneNameLabel : $timezoneName;
    }

    /**
     * @return string
     */
    public function getDbVersion(): string
    {
        $conn = Db::getWriteConn();
        $names = explode('_', get_class($conn));
        $name = end($names);
        return $name . ' ' . $conn->getDbVersion();
    }
}

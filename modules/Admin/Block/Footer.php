<?php

namespace Admin\Block;

use Core\Block\AbstractModel;
use Core\Model\Date\Timezone;
use Ddm;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Footer extends AbstractModel
{

    public function getRunTime(): mixed
    {
        return $this->getTemplateObject()->getRunTime();
    }

    public function getQueryCount(): mixed
    {
        return $this->getTemplateObject()->getQueryCount();
    }

    public function getMemoryUsage(): mixed
    {
        return $this->getTemplateObject()->getMemoryUsage();
    }

    public function getCopyrightInfo(): mixed
    {
        return 'Ddmcms ver. ' . _VERSION_ . ' Copyright &copy; 2014 <a href="https://code.google.com/p/ddmcms/source/checkout" target="_brank">Open source projects</a>';
    }

    public function getTimezoneName(): mixed
    {
        $timezoneName = Ddm::getHelper('core')->getDateTime()->getTimezone()->getName();
        $timezoneNameLabel = Timezone::singleton()->getTimezones($timezoneName);
        return $timezoneNameLabel ? $timezoneNameLabel : $timezoneName;
    }
}

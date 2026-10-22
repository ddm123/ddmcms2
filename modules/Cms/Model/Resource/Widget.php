<?php

namespace Cms\Model\Resource;

use Core\Model\AbstractModel;
use Core\Model\Resource\Entity;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Widget extends Entity
{

    protected function _init(): static
    {
        $this->_setTable('widget', 'widget_id');
        return $this;
    }
}

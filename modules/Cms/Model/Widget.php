<?php

namespace Cms\Model;

use Core\Model\AbstractModel;
use Core\Model\Entity;
use Ddm;
use Ddm\Request;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
/**
 * @method \Cms\Model\Resource\Widget getResource()
 */
class Widget extends Entity
{

    protected static mixed $_instance = null;

    protected function _initEntity(): void
    {
        $this->setEntity('widget')->setEntityPrimarykey('widget_id');
    }

    /**
     * 使用单例模式
     * @return \Cms\Model\Widget
     */
    public static function singleton(): Widget
    {
        return self::$_instance === null ? self::$_instance = new Widget() : self::$_instance;
    }

    /**
     * @param mixed $identifier
     * @return $this
     */
    public function loadFromIdentifier(mixed $identifier): static
    {
        if ($identifier) {
            $this->load($identifier, 'identifier');
        }
        return $this;
    }

    protected function _beforeSave(): static
    {
        if ($this->create_at === null) {
            $this->create_at = Request::server()->REQUEST_TIME;
        }
        $this->update_at = Request::server()->REQUEST_TIME;
        if ($this->identifier) {
            $this->identifier = preg_replace('/[^\w\-\.\/]/', '-', $this->identifier);
        } else {
            throw new \Exception('Identifier can not be empty');
        }
        return parent::_beforeSave();
    }

    protected function _afterSave(): static
    {
        Ddm::getHelper('Cms')->removeWidgetCache($this->identifier);
        return parent::_afterSave();
    }

    protected function _afterDelete(): static
    {
        Ddm::getHelper('Cms')->removeWidgetCache($this->identifier);
        return parent::_afterDelete();
    }
}

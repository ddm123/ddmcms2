<?php

namespace Cms\Model;

use Core\Model\AbstractModel;
use Core\Model\Entity;
use Ddm;
use Ddm\Cache;
use Ddm\Request;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
/**
 * @method \Cms\Model\Resource\Onepage getResource()
 */
class Onepage extends Entity
{

    protected static mixed $_instance = null;

    protected function _initEntity(): void
    {
        $this->setEntity('onepage')->setEntityPrimarykey('onepage_id');
    }

    /**
     * 使用单例模式
     * @return \Cms\Model\Onepage
     */
    public static function singleton(): Onepage
    {
        return self::$_instance === null ? self::$_instance = new Onepage() : self::$_instance;
    }

    /**
     * @param bool $withEmpty
     * @return array
     */
    public function getOnepageToOption(bool $withEmpty = false): array
    {
        return Ddm::getHelper('cms')->getOnepageToOption($withEmpty);
    }

    protected function _beforeSave(): static
    {
        if ($this->create_at === null) {
            $this->create_at = Request::server()->REQUEST_TIME;
        }
        $this->update_at = Request::server()->REQUEST_TIME;
        if ($this->url_key) {
            $this->url_key = preg_replace('/[^\w\-\.\/]/', '-', $this->url_key);
        } elseif (!$this->language_id || $this->language_id === '0') {
            throw new \Exception('"url_key" field can not be empty');
        }
        return parent::_beforeSave();
    }

    protected function _afterSave(): static
    {
        Ddm::getHelper('Cms')->removeOnepageCache($this->getId());
        Cache::remove(Helper::ONEPAGE_URLS_CACHE_KEY);
        return parent::_afterSave();
    }

    protected function _afterDelete(): static
    {
        Ddm::getHelper('Cms')->removeOnepageCache($this->getId());
        Cache::remove(Helper::ONEPAGE_URLS_CACHE_KEY);
        return parent::_afterDelete();
    }
}

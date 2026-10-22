<?php

namespace Cms\Model;

use Ddm;
use Ddm\Controller;
use Ddm\Db;
use Ddm\Request;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Observer
{

    protected static mixed $_instance = null;

    /**
     * 使用单例模式
     * @return \Cms\Model\Observer
     */
    public static function singleton(): Observer
    {
        return self::$_instance === null ? self::$_instance = new self() : self::$_instance;
    }

    /**
     * @param array $params
     * @return $this
     */
    public function deleteConfigFromLanguage(array $params): static
    {
        if ($languageId = (int) $params['object']->getId()) {
            foreach ($this->_getCmsDataValueTables() as $table) {
                Db::table($table, true)->where('language_id', '=', $languageId)->delete();
            }
        }
        return $this;
    }

    /**
     * @param array $params
     * @return $this
     */
    public function deleteConfigFromAttribute(array $params): static
    {
        if ($attributeId = (int) $params['object']->getId()) {
            foreach ($this->_getCmsDataValueTables() as $table) {
                Db::table($table, true)->where('attribute_id', '=', $attributeId)->delete();
            }
        }
        return $this;
    }

    /**
     * @param array $params
     * @return $this
     */
    public function matchesCmsPage(array $params): static
    {
        if ($params['controller']->runModule()) {
            return $this;
        }
        $urlKey = $params['controller']->getUriPath();
        $onepageId = intval($urlKey == '' ? Ddm::getConfig()->getConfigValue('web/pages/home') : Ddm::getHelper('cms')->getOnepageIdFromUrlKey($urlKey)) or defined('IN_ADMIN') and IN_ADMIN or $onepageId = intval(Ddm::getConfig()->getConfigValue('web/pages/not_found'));
        if ($onepageId) {
            if ($onepageId == (int) Ddm::getConfig()->getConfigValue('web/pages/not_found')) {
                Controller::outputNoActionHeader();
            }
            if ($onepageId == Ddm::getConfig()->getConfigValue('web/pages/home')) {
                Controller::singleton()->setHomePage(true);
            }
            $params['controller']->setModuleName('Cms')->setControllerName('index')->setActionName('view');
            $params['controller']->resetRunModule();
            Request::singleton()->setParam('id', $onepageId);
        }
        return $this;
    }

    /**
     * 获取CMS模块所有属性值表
     * @return array
     */
    protected function _getCmsDataValueTables(): array
    {
        $onepage = new Onepage();
        $widget = new Widget();
        return array_merge($onepage->getResource()->getAttributeTables($onepage), $widget->getResource()->getAttributeTables($widget));
    }
}

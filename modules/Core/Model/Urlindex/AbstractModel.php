<?php

namespace Core\Model\Urlindex;

use Core\Model\Attribute;
use Core\Model\Url;
use Ddm;
use Ddm\Cache;
use Ddm\Db;
use Ddm\Db\Expression;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
abstract class AbstractModel
{

    protected mixed $_urlKeyAttribute = null;

    protected mixed $_module = null;

    protected mixed $_controller = null;

    protected mixed $_action = null;

    protected mixed $_params = null;

    public function __construct()
    {
        //;
    }

    /**
     * @param Attribute $attribute
     * @return $this
     */
    public function setUrlKeyAttribute(Attribute $attribute): static
    {
        $this->_urlKeyAttribute = $attribute;
        return $this;
    }

    /**
     * @return Attribute
     */
    public function getUrlKeyAttribute(): Attribute
    {
        return $this->_urlKeyAttribute;
    }

    /**
     * @param string $module
     * @return $this
     */
    public function setModule(string $module): static
    {
        $this->_module = $module;
        return $this;
    }

    /**
     * @return string
     */
    public function getModule(): string
    {
        return $this->_module;
    }

    /**
     * @param string $controller
     * @return $this
     */
    public function setController(string $controller): static
    {
        $this->_controller = $controller;
        return $this;
    }

    /**
     * @return string
     */
    public function getController(): string
    {
        return $this->_controller;
    }

    /**
     * @param string $action
     * @return $this
     */
    public function setAction(string $action): static
    {
        $this->_action = $action;
        return $this;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->_action;
    }

    /**
     * @param array $params
     * @return $this
     */
    public function setParams(array $params): static
    {
        $this->_params = $params;
        return $this;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return $this->_params;
    }

    /**
     * @param int $entityId
     * @return $this
     */
    public function saveUrlIndex(int $entityId): static
    {
        if (!$this->getUrlKeyAttribute()) {
            throw new \Exception('Property \'_urlKeyAttribute\' is undefined.');
        }
        if (!$this->getModule()) {
            throw new \Exception('Property \'_module\' is undefined.');
        }
        if ((int) $entityId) {
            $urlKeys = Db::table($this->getUrlKeyAttribute()->getTable(), true)->where('entity_id', '=', (int) $entityId)->where('attribute_id', '=', (int) $this->getUrlKeyAttribute()->getId())->pluck('value', 'language_id');
            if ($urlKeys) {
                foreach (Ddm::getLanguage()->getAllLanguage(false) as $language) {
                    isset($urlKeys[$language['language_id']]) or $urlKeys[$language['language_id']] = $urlKeys[0];
                }
                if ($params = $this->getParams()) {
                    foreach ($params as $key => $value) {
                        if ($value == '{entity_id}') {
                            $params[$key] = (int) $entityId;
                        }
                    }
                }
                Url::singleton()->saveUrl($urlKeys, $this->getModule(), $this->getController(), $this->getAction(), $params);
            }
        }
        return $this;
    }

    /**
     * @param int $from
     * @param int $to
     * @return \Core\Model\Urlindex\AbstractModel
     */
    public function saveUrlIndexFromCategoryId(int $from, int $to): AbstractModel
    {
        if (!$this->getUrlKeyAttribute()) {
            throw new \Exception('Property \'_urlKeyAttribute\' is undefined.');
        }
        if (!$this->getModule()) {
            throw new \Exception('Property \'_module\' is undefined.');
        }
        $from = (int) $from;
        $to = (int) $to;
        $urlKeys = Db::table($this->getUrlKeyAttribute()->getTable(), true)->whereBetween('entity_id', $from, $to)->where('attribute_id', '=', (int) $this->getUrlKeyAttribute()->getId())->get(['key' => new Expression('CONCAT(entity_id,' - ',language_id)'), 'entity_id', 'language_id', 'value'], 'key');
        if ($urlKeys) {
            $data = [];
            $allLanguages = Ddm::getLanguage()->getAllLanguage(true);
            $idField = null;
            if ($params = $this->getParams()) {
                foreach ($params as $key => $value) {
                    if ($value == '{entity_id}') {
                        $idField = $key;
                        break;
                    }
                }
                if (!$idField) {
                    $params = serialize($params);
                }
            }
            foreach ($urlKeys as $_urlKey) {
                foreach ($allLanguages as $language) {
                    if (isset($urlKeys[$_urlKey['entity_id'] . '-' . $language['language_id']])) {
                        $urlKey = $urlKeys[$_urlKey['entity_id'] . '-' . $language['language_id']]['value'];
                    } elseif (isset($urlKeys[$_urlKey['entity_id'] . '-0'])) {
                        $urlKey = $urlKeys[$_urlKey['entity_id'] . '-0']['value'];
                    } else {
                        $urlKey = $_urlKey['value'];
                    }
                    if ($urlKey) {
                        if ($idField) {
                            $params = $this->getParams();
                            $params[$idField] = $_urlKey['entity_id'];
                            $params = serialize($params);
                        }
                        $data[] = ['url_path' => $this->getModule() . "/{$urlKey}", 'language_id' => $language['language_id'], 'module' => (string) $this->getModule(), 'controller' => (string) $this->getController(), 'action' => (string) $this->getAction(), 'params' => (string) $params];
                    }
                }
            }
            if ($data) {
                Db::getWriteConn()->insertMultiple(Url::singleton()->getMainTable(), $data, false, ['module', 'controller', 'action', 'params']);
            }
        }
        return $this;
    }

    /**
     * @param bool $checkCache
     * @return $this
     * @throws Exception
     */
    public function clearUrlIndex(bool $checkCache = true): static
    {
        if (!$this->getUrlKeyAttribute()) {
            throw new \Exception('Property \'_urlKeyAttribute\' is undefined.');
        }
        if (!$this->getModule()) {
            throw new \Exception('Property \'_module\' is undefined.');
        }
        $cacheKey = 'remove_not_exist_url_' . $this->getUrlKeyAttribute()->getId();
        if (!$checkCache || Cache::load($cacheKey) == false) {
            Db::table('url_index')->where('module', '=', (string) $this->getModule())->where('controller', '=', (string) $this->getController())->where('action', '=', (string) $this->getAction())->delete();
            Cache::save($cacheKey, '1', [], 86400);
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function removeClearUrlIndexCache(): static
    {
        if ($this->getUrlKeyAttribute()) {
            Cache::remove('remove_not_exist_url_' . $this->getUrlKeyAttribute()->getId());
        }
        return $this;
    }
}

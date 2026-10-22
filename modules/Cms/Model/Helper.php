<?php

namespace Cms\Model;

use Ddm;
use Ddm\Cache;
use Ddm\Db;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Helper
{

    public const ONEPAGE_CACHEKEY_PREFIX = 'cms_onepage_content_';

    public const WIDGET_CACHEKEY_PREFIX = 'cms_widget_content_';

    public const ONEPAGE_URLS_CACHE_KEY = 'cms_onepage_urls';

    protected mixed $_onepageOptions = null;

    /**
     * @param \Cms\Model\Onepage $onepage
     * @return Cache|bool
     */
    public function saveOnepageCache(Onepage $onepage): Cache|bool
    {
        if ($onepage->getId() && is_numeric($onepage->cache_lifetime) && $onepage->cache_lifetime >= 0) {
            $data = $onepage->getData();
            if (is_array($data) && !empty($data['content'])) {
                $data['content'] = preg_replace("/(<\\/?\\w+[^>]*>)\\s*[\r\n]+\\s*/", '\1', $data['content']);
            }
            return Cache::save(self::ONEPAGE_CACHEKEY_PREFIX . $onepage->getId() . '_' . Ddm::getLanguage()->language_id, $data, ['HTML_BLOCK', 'onepage'], $onepage->cache_lifetime * 60);
        }
        return false;
    }

    /**
     * @param int $onepageId
     * @return Cache
     */
    public function removeOnepageCache(int $onepageId): Cache
    {
        return Cache::remove(self::ONEPAGE_CACHEKEY_PREFIX . $onepageId . '_' . Ddm::getLanguage()->language_id);
    }

    /**
     * @param int $onepageId
     * @return string
     */
    public function getOnepageCache(int $onepageId): string
    {
        return Cache::load(self::ONEPAGE_CACHEKEY_PREFIX . $onepageId . '_' . Ddm::getLanguage()->language_id);
    }

    /**
     * @param \Cms\Model\Widget $widget
     * @return Cache|bool
     */
    public function saveWidgetCache(Widget $widget): Cache|bool
    {
        if ($widget->identifier && is_numeric($widget->cache_lifetime) && $widget->cache_lifetime >= 0) {
            $data = $widget->getData();
            if (is_array($data) && !empty($data['content'])) {
                $data['content'] = preg_replace("/(<\\/?\\w+[^>]*>)\\s*[\r\n]+\\s*/", '\1', $data['content']);
            }
            return Cache::save(self::WIDGET_CACHEKEY_PREFIX . $widget->identifier . '_' . Ddm::getLanguage()->language_id, $data, ['HTML_BLOCK', 'widget'], $widget->cache_lifetime * 60);
        }
        return false;
    }

    /**
     * @param string $identifier
     * @return Cache
     */
    public function removeWidgetCache(string $identifier): Cache
    {
        return Cache::remove(self::WIDGET_CACHEKEY_PREFIX . $identifier . '_' . Ddm::getLanguage()->language_id);
    }

    /**
     * @param string $identifier
     * @return string
     */
    public function getWidgetCache(string $identifier): string
    {
        return Cache::load(self::WIDGET_CACHEKEY_PREFIX . $identifier . '_' . Ddm::getLanguage()->language_id);
    }

    /**
     * @param bool $withEmpty
     * @return array
     */
    public function getOnepageToOption(bool $withEmpty = false): array
    {
        if ($this->_onepageOptions === null) {
            $this->_onepageOptions = [];
            $onepage = new Onepage();
            $onepage->setLanguageId(Ddm::getLanguage()->language_id)->addAttributeToSelect('title')->addAttributeToFilter('is_enabled', 1);
            foreach ($onepage->getSelect()->get() as $opt) {
                $this->_onepageOptions[] = ['value' => $opt['onepage_id'], 'label' => $opt['title']];
            }
        }
        return $withEmpty ? array_merge([['value' => '', 'label' => '']], $this->_onepageOptions) : $this->_onepageOptions;
    }

    /**
     * @param string $urlKey
     * @return int
     */
    public function getOnepageIdFromUrlKey(string $urlKey): int
    {
        $onepageUrls = Cache::load(self::ONEPAGE_URLS_CACHE_KEY);
        if ($onepageUrls === false) {
            $onepageUrls = [];
            if ($urlKeyAttribute = Ddm::getHelper('core')->getEntityAttribute('onepage', 'url_key')) {
                $result = Db::table($urlKeyAttribute->getTable(), true)->select(['entity_id', 'language_id', 'value'])->where('attribute_id', '=', $urlKeyAttribute->attribute_id)->get();
                foreach ($result as $row) {
                    isset($onepageUrls[$row['value']]) or $onepageUrls[$row['value']] = [];
                    $onepageUrls[$row['value']][$row['language_id']] = $row['entity_id'];
                }
            }
            Cache::save(self::ONEPAGE_URLS_CACHE_KEY, $onepageUrls, ['onepage'], 0);
        }
        if (Ddm::getLanguage()->language_id && isset($onepageUrls[$urlKey][Ddm::getLanguage()->language_id])) {
            return $onepageUrls[$urlKey][Ddm::getLanguage()->language_id];
        }
        return isset($onepageUrls[$urlKey][0]) ? $onepageUrls[$urlKey][0] : 0;
    }
}

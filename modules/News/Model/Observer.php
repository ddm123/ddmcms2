<?php

namespace News\Model;

use Cms\Model\Observer as ModelObserver;
use Ddm;
use Ddm\Db;

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

    protected mixed $_newsDataValueTables = null;

    /**
     * 使用单例模式
     * @return \News\Model\News
     */
    public static function singleton(): News
    {
        return self::$_instance === null ? self::$_instance = new self() : self::$_instance;
    }

    /**
     * @param array $params
     * @return $this
     */
    public function addCacheItem(array $params): static
    {
        $params['cache_block']->addItem('news_cache', ['tags' => 'news_list,news_view', 'label' => Ddm::getTranslate('news')->translate('新闻缓存'), 'description' => Ddm::getTranslate('news')->translate('新闻列表页和详细页的缓存')]);
        return $this;
    }

    /**
     * @param array $params
     * @return $this
     */
    public function deleteValueFromLanguage(array $params): static
    {
        if ($languageId = (int) $params['object']->getId()) {
            foreach ($this->_getNewsDataValueTables() as $table) {
                Db::table($table, true)->where('language_id', '=', $languageId)->delete();
            }
        }
        return $this;
    }

    /**
     * @param array $params
     * @return $this
     */
    public function deleteValuegFromAttribute(array $params): static
    {
        if ($attributeId = (int) $params['object']->getId()) {
            foreach ($this->_getNewsDataValueTables() as $table) {
                Db::table($table, true)->where('attribute_id', '=', $attributeId)->delete();
            }
        }
        return $this;
    }

    /**
     * 获取News模块所有属性值表
     * @return array
     */
    protected function _getNewsDataValueTables(): array
    {
        if ($this->_newsDataValueTables === null) {
            $category = new Category();
            $news = new News();
            $this->_newsDataValueTables = array_merge($category->getResource()->getAttributeTables($category), $news->getResource()->getAttributeTables($news));
        }
        return $this->_newsDataValueTables;
    }
}

<?php

namespace News\Model;

use Core\Model\AbstractModel;
use Core\Model\Entity;
use Ddm;
use Ddm\Db\Expression;
use Ddm\Db\JoinClause;
use Ddm\Request;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
/**
 * @method \News\Model\Resource\News getResource()
 */
class News extends Entity
{

    protected static mixed $_instance = null;

    protected mixed $_url = null;

    protected function _initEntity(): void
    {
        $this->setEntity('news')->setEntityPrimarykey('news_id');
    }

    /**
     * 使用单例模式
     * @return \News\Model\News
     */
    public static function singleton(): News
    {
        return self::$_instance === null ? self::$_instance = new self() : self::$_instance;
    }

    /**
     * @return $this
     */
    public function addCategoryToSelect(): static
    {
        $attribute = Ddm::getHelper('core')->getEntityAttribute('news_category', 'name');
        if ($attribute) {
            $languageId = intval($this->language_id === null ? Ddm::getLanguage()->language_id : $this->language_id);
            $this->getSelect()->leftJoin(['category_name_d' => $attribute->getTable(), true], function (JoinClause $join) use ($attribute) {
                $join->on('category_name_d.entity_id', '=', 'main_table.category_id');
                $join->where('category_name_d.attribute_id', '=', $attribute->attribute_id);
                $join->where('category_name_d.language_id', '=', 0);
            });
            if ($languageId) {
                $this->getSelect()->leftJoin(['category_name_l' => $attribute->getTable(), true], function (JoinClause $join) use ($attribute, $languageId) {
                    $join->on('category_name_l.entity_id', '=', 'main_table.category_id');
                    $join->where('category_name_l.attribute_id', '=', $attribute->attribute_id);
                    $join->where('category_name_l.language_id', '=', $languageId);
                })->addSelect(['category_name' => new Expression('IFNULL(category_name_l.value,category_name_d.value)')]);
            } else {
                $this->getSelect()->addSelect(['category_name' => 'category_name_d.value']);
            }
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function addCategoryUrlToSelect(): static
    {
        $attribute = Ddm::getHelper('core')->getEntityAttribute('news_category', 'url_key');
        if ($attribute) {
            $languageId = intval($this->language_id === null ? Ddm::getLanguage()->language_id : $this->language_id);
            $this->getSelect()->leftJoin(['category_url_d' => $attribute->getTable(), true], function (JoinClause $join) use ($attribute) {
                $join->on('category_url_d.entity_id', '=', 'main_table.category_id');
                $join->where('category_url_d.attribute_id', '=', $attribute->attribute_id);
                $join->where('category_url_d.language_id', '=', 0);
            });
            if ($languageId) {
                $this->getSelect()->leftJoin(['category_url_l' => $attribute->getTable(), true], function (JoinClause $join) use ($attribute, $languageId) {
                    $join->on('category_url_l.entity_id', '=', 'main_table.category_id');
                    $join->where('category_url_l.attribute_id', '=', $attribute->attribute_id);
                    $join->where('category_url_l.language_id', '=', $languageId);
                })->addSelect(['category_url' => new Expression('IFNULL(category_url_l.value,category_url_d.value)')]);
            } else {
                $this->getSelect()->addSelect(['category_url' => 'category_url_d.value']);
            }
        }
        return $this;
    }

    /**
     * 根据url_key获取一条数据
     * @param string $urlKey
     * @return $this
     */
    public function loadFromUrlKey(string $urlKey): static
    {
        if ($urlKey) {
            $this->getResource()->loadFromUrlKey($this, $urlKey);
            $this->_afterLoad();
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getNewsUrl(): string
    {
        if ($this->news_id) {
            return $this->_url === null ? $this->_url = Ddm::getHelper('news')->getNewsUrlFromUrlKey($this->news_id, $this->url_key) : $this->_url;
        }
        return null;
    }

    /**
     * @param int $categoryId
     * @return $this
     */
    public function addCategoryToFilter(int $categoryId): static
    {
        $this->getSelect()->where('main_table.category_id', '=', (int) $categoryId);
        return $this;
    }

    protected function _beforeSave(): static
    {
        if ($this->create_at === null) {
            $this->create_at = Request::server()->REQUEST_TIME;
        }
        $this->update_at = Request::server()->REQUEST_TIME;
        if ($this->url_key) {
            $this->url_key = preg_replace('/[^\w\-\.\/]/', '-', $this->url_key);
            if (!preg_match('/(?:\/|\.html?)$/i', $this->url_key)) {
                $this->url_key .= '.html';
            }
        }
        if ($this->category_id = (int) $this->category_id) {
            //验证是否真的有该分类
            Category::singleton()->categoryExists($this->category_id) or $this->category_id = 0;
        }
        return parent::_beforeSave();
    }
}

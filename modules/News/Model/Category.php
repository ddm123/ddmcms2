<?php

namespace News\Model;

use Core\Model\AbstractModel;
use Core\Model\Entity;
use Ddm;
use Ddm\Db;
use Ddm\Request;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
/**
 * @method \News\Model\Resource\Category getResource()
 */
class Category extends Entity
{

    protected static mixed $_instance = null;

    protected mixed $_url = null;

    protected function _initEntity(): void
    {
        $this->setEntity('news_category')->setEntityPrimarykey('category_id');
    }

    /**
     * 使用单例模式
     * @return \News\Model\Category
     */
    public static function singleton(): Category
    {
        return self::$_instance === null ? self::$_instance = new self() : self::$_instance;
    }

    /**
     * 返回下一个可用的排序位置
     * @return int
     */
    public function getMaxPosition(): int
    {
        return $this->getResource()->getMaxPosition();
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
    public function getCategoryUrl(): string
    {
        if ($this->category_id) {
            return $this->_url === null ? $this->_url = Ddm::getHelper('news')->getCategoryUrlFromUrlKey($this->category_id, $this->url_key) : $this->_url;
        }
        return null;
    }

    /**
     * 检测分类是否是存在的
     *
     * @param mixed $value
     * @param string $fieldName
     * @return bool
     * @throws Exception
     */
    public function categoryExists(mixed $value, string $fieldName = 'category_id'): bool
    {
        if (empty($fieldName)) {
            throw new \Exception('Field name can not be empty');
        }
        return Db::table('news_category')->where($fieldName, '=', $value)->exists();
    }

    protected function _beforeSave(): static
    {
        if ($this->create_at === null) {
            $this->create_at = Request::server()->REQUEST_TIME;
        }
        $this->update_at = Request::server()->REQUEST_TIME;
        $this->position = (int) $this->position;
        if ($this->url_key) {
            $this->url_key = preg_replace('/[^\w\-\.\/]/', '-', $this->url_key);
            if (!preg_match('/(?:\/|\.html?)$/i', $this->url_key)) {
                $this->url_key .= '.html';
            }
        }
        return parent::_beforeSave();
    }
}

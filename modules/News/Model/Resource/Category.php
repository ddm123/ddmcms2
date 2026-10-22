<?php

namespace News\Model\Resource;

use Core\Model\AbstractModel;
use Core\Model\Resource\AbstractModel as ResourceAbstractModel;
use Core\Model\Resource\Entity;
use Core\Model\Url;
use Ddm;
use Ddm\Db;
use Ddm\Db\Expression;
use News\Model\Category as ModelCategory;
use News\Model\Category\Urlindex;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Category extends Entity
{

    protected function _init(): static
    {
        $this->_setTable('news_category', 'category_id');
        return $this;
    }

    /**
     * 返回下一个可用的排序位置
     * @return int
     */
    public function getMaxPosition(): int
    {
        return Db::table($this->getMainTableName())->value(new Expression('MAX(position)')) + 1;
    }

    /**
     * @param ModelCategory $category
     * @param string $urlKey
     * @return $this
     */
    public function loadFromUrlKey(ModelCategory $category, string $urlKey): static
    {
        $_category = new ModelCategory();
        $result = $_category->setLanguageId($category->language_id)->addAttributeToSelect('url_key')->addAttributeToFilter('url_key', $urlKey)->getSelect()->first();
        if ($result) {
            $category->addData($result)->setOrigData($result, null, true);
        }
        return $this;
    }

    /**
     * 获取全部分类下的新闻记录数
     * @return array
     */
    public function getNewsCount(): array
    {
        return Db::table(['a' => 'news_category'])->leftJoin(['b' => 'news'], 'b.category_id', '=', 'a.category_id')->groupBy(['a.category_id'])->orderBy(null)->pluck(new Expression('COUNT(b.news_id)'), 'a.category_id');
    }

    /**
     * @param int $categoryId
     * @return $this
     */
    public function saveUrlIndex(int $categoryId): static
    {
        if (Ddm::getHelper('core')->getEntityAttribute('news_category', 'url_key')) {
            $urlIndex = new Urlindex();
            $urlIndex->saveUrlIndex($categoryId);
        }
        return $this;
    }

    protected function _afterSave(AbstractModel $model): static
    {
        parent::_afterSave($model);
        //必须在第一行
        if ($model->url_key != $model->getOrigData('url_key')) {
            //删掉旧的URL记录
            Url::singleton()->removeFromUrlKey('news', $model->getOrigData('url_key'));
            $this->saveUrlIndex($model->getId());
        }
        return $this;
    }

    protected function _afterDelete(AbstractModel $object): static
    {
        if ($object->getId()) {
            Db::table('news')->where('category_id', '=', $object->getId())->update(['category_id' => 0]);
        }
        return parent::_afterDelete($object);
    }
}

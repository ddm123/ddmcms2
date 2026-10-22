<?php

namespace News\Block\News;

use Core\Block\AbstractModel;
use Ddm;
use News\Model\Category as ModelCategory;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
/**
 * @method \News\Block\News getParentBlock()
 */
class Category extends AbstractModel
{

    protected mixed $_categories = null;

    public function init(): static
    {
        $this->setTemplate('category.phtml');
        return parent::init();
    }

    /**
     * @return ModelCategory
     */
    public function getCategory(): ModelCategory
    {
        return $this->getParentBlock()->getCategory();
    }

    /**
     * @return array
     */
    public function getCategories(): array
    {
        if ($this->_categories === null) {
            $category = new ModelCategory();
            $this->_categories = $category->setLanguageId(Ddm::getLanguage()->language_id)->addAttributeToSelect('name')->addAttributeToSelect('url_key')->getSelect()->orderBy('main_table.position', 'ASC')->get();
        }
        return $this->_categories;
    }

    protected function _beforeToHtml(): static
    {
        $categoryId = $this->getCategory() ? $this->getParentBlock()->getCategoryId() : 0;
        $this->setCache('news_category_list_' . $categoryId, ['news_list'], 43200);
        //缓存12个小时, 其实可以更长
        return parent::_beforeToHtml();
    }
}

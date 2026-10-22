<?php

namespace News\Block;

use Core\Block\AbstractModel;
use Ddm;
use Ddm\Request;
use News\Model\Category;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class News extends AbstractModel
{

    protected string $_categoryVarName = 'category';

    protected int $_categoryId = 0;

    protected bool $_category = false;

    public function init(): static
    {
        $description = Ddm::getTranslate('news')->translate('新闻中心');
        $keywords = Ddm::getTranslate('news')->translate('新闻中心');
        $this->getTemplateObject()->setTitle(Ddm::getTranslate('news')->translate('新闻中心'));
        $this->getTemplateObject()->getBreadcrumb()->addCrumb(['label' => Ddm::getTranslate('news')->translate('新闻中心'), 'link' => Ddm::getUrl('news')]);
        $this->setTemplate('news.phtml');
        $this->addBlock($this->createBlock('news', 'news_hot'), 'hot')->addBlock($this->createBlock('news', 'news_category'), 'category')->addBlock($this->createBlock('news', 'news_list'), 'list');
        if ($this->getCategory()) {
            $this->getTemplateObject()->addTitle($this->_category->name);
            $this->getTemplateObject()->getBreadcrumb()->addCrumb(['label' => $this->_category->name, 'link' => $this->_category->getCategoryUrl()]);
            $description .= ', ' . $this->_category->name;
            $keywords .= ',' . $this->_category->name;
        }
        $this->getTemplateObject()->addMeta('description', $description)->addMeta('keywords', $keywords);
        return parent::init();
    }

    /**
     * @return Category
     */
    public function getCategory(): Category
    {
        if ($this->_category === false) {
            $this->_category = Ddm::registry('current_category');
            if (!$this->_category && (int) Request::get($this->_categoryVarName)) {
                $this->_category = new Category();
                $this->_category->load((int) Request::get($this->_categoryVarName));
                if ($this->_categoryId = (int) $this->_category->getId()) {
                    Ddm::register('current_category', $this->_category);
                } else {
                    $this->_category = null;
                }
            } elseif ($this->_category) {
                $this->_categoryId = (int) $this->_category->getId();
            } else {
                $this->_categoryId = 0;
            }
        }
        return $this->_category;
    }

    /**
     * @return int
     */
    public function getCategoryId(): int
    {
        return $this->_categoryId;
    }

    /**
     * @param Category $category
     * @return $this
     */
    public function setCategory(Category $category): static
    {
        $this->_category = $category;
        return $this;
    }
}

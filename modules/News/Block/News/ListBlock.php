<?php

namespace News\Block\News;

use Core\Block\AbstractModel as BlockAbstractModel;
use Core\Block\Frontend\List\AbstractModel;
use Ddm;
use Ddm\Request;
use News\Model\Category;
use News\Model\News;

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
class ListBlock extends AbstractModel
{

    private mixed $_categoryId = null;

    protected string $_searchVarName = 'q';

    public function init(): static
    {
        $this->setTemplate('list.phtml');
        if ($pageSize = (int) Ddm::getConfig()->getConfigValue('news/display/pagesize')) {
            $this->setPageSize($pageSize);
        }
        $this->setOrderBy((int) Ddm::getConfig()->getConfigValue('news/display/orderby') == 1 ? 'main_table.update_at DESC' : 'main_table.news_id DESC');
        $this->getPageLinkBlock()->setPageVars('_use_alias', true);
        if ($q = trim(Request::get($this->getSearchVarName(), false, ''))) {
            $this->getTemplateObject()->addTitle(Ddm::getTranslate('core')->translate('搜索') . ': ' . $q)->getBreadcrumb()->addCrumb(['label' => Ddm::getTranslate('core')->translate('搜索') . ': ' . $q]);
        }
        return parent::init();
    }

    /**
     * @return string
     */
    public function getSearchVarName(): string
    {
        return $this->_searchVarName;
    }

    /**
     * @return Category
     */
    public function getCategory(): Category
    {
        return $this->getParentBlock()->getCategory();
    }

    /**
     * @return int
     */
    public function getCategoryId(): int
    {
        if ($this->_categoryId === null) {
            $this->_categoryId = $this->getCategory() ? $this->getParentBlock()->getCategoryId() : 0;
        }
        return $this->_categoryId;
    }

    /**
     * @return News
     */
    public function getModelObject(): News
    {
        if ($this->_modelObject === null) {
            $this->_modelObject = new News();
            $this->_modelObject->setLanguageId(Ddm::getLanguage()->language_id);
        }
        return $this->_modelObject;
    }

    protected function _beforeGetCount(): static
    {
        if ($this->getCategoryId()) {
            $this->getModelObject()->addCategoryToFilter($this->getCategoryId());
        } else {
            $this->getModelObject()->addCategoryToSelect()->addCategoryUrlToSelect();
        }
        $this->getModelObject()->addAttributeToSelect('title');
        if ($q = trim(Request::get($this->getSearchVarName(), false, ''))) {
            $this->getModelObject()->addAttributeToFilter('title', ['like' => '%' . $q . '%']);
        }
        Ddm::dispatchEvent('parse_get_newscount_after', ['block' => $this]);
        return parent::_beforeGetCount();
    }

    protected function _beforeGetList(): static
    {
        $this->getModelObject()->addAttributeToSelect('url_key')->addAttributeToSelect('content');
        Ddm::dispatchEvent('parse_get_newslist_after', ['block' => $this]);
        return parent::_beforeGetList();
    }

    protected function _beforeToHtml(): static
    {
        //不缓存搜索结果
        if (trim(Request::get($this->getSearchVarName(), false, '')) == '') {
            $cacheLifetime = Ddm::getConfig()->getConfigValue('news/cache/list');
            $cacheLifetime = $cacheLifetime === null ? 1800 : $cacheLifetime * 60;
            //小于5分钟的缓存没任何意义, 所以这里只有设置大于5分钟以上才启用缓存
            if ($cacheLifetime === 0 || $cacheLifetime >= 300) {
                $this->setCache('news_list_' . $this->getCategoryId() . '_' . $this->getPageLinkBlock()->getCurrentPage(), ['news_list'], $cacheLifetime);
            }
        }
        return parent::_beforeToHtml();
    }
}

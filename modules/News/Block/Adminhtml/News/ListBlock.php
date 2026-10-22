<?php

namespace News\Block\Adminhtml\News;

use Admin\Block\List\AbstractModel;
use Ddm;
use Ddm\Db\Expression;
use Ddm\Db\JoinClause;
use Ddm\Request;
use News\Model\Category\Option;
use News\Model\News;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class ListBlock extends AbstractModel
{

    protected bool $_languageId = false;

    /** @var News|null */
    protected mixed $_news = null;

    /**
     * @return $this
     */
    protected function _initGrid(): static
    {
        $this->_grid = $this->_createGridBlock();
        $this->_grid->titleText = Ddm::getTranslate('news')->translate('新闻');
        $this->_grid->defaultSort = 'news_id';
        //是$columnId, 而不是字段名
        $this->_grid->defaultDir = 'DESC';
        $this->_grid->primaryKey = 'news_id';
        $this->_grid->saveFieldValueUrl = Ddm::getUrl('*/*/save-field-value');
        $languageId = $this->getLanguageId();
        $this->_news = new News();
        $this->_news->setLanguageId($languageId)->addAttributeToSelect('title')->addAttributeToSelect('author');
        if ($categoryAttribute = Ddm::getHelper('core')->getEntityAttribute('news_category', 'name')) {
            $this->_news->getSelect()->leftJoin(['category' => $categoryAttribute->getTable(), true], function (JoinClause $join) use ($categoryAttribute) {
                $join->on('category.entity_id', '=', 'main_table.category_id');
                $join->where('category.attribute_id', '=', $categoryAttribute->getId());
                $join->where('category.language_id', '=', 0);
            });
            if ($languageId) {
                $this->_news->getSelect()->leftJoin(['category2' => $categoryAttribute->getTable(), true], function (JoinClause $join) use ($categoryAttribute, $languageId) {
                    $join->on('category2.entity_id', '=', 'main_table.category_id');
                    $join->where('category2.attribute_id', '=', $categoryAttribute->getId());
                    $join->where('category2.language_id', '=', $languageId);
                })->addSelect(['category_name' => new Expression("IF(main_table.category_id='0','-',IFNULL(category2.value, category.value))")]);
            } else {
                $this->_news->getSelect()->addSelect(['category_name' => new Expression('IFNULL(category.value,\'-\')')]);
            }
        }
        $this->_grid->setSelect($this->_news->getSelect())->setResetGridSearchUrl(Ddm::getLanguage()->getUrl('*/*', ['language' => $this->getLanguageId()]));
        if (Ddm::getHelper('core')->getEntityAttribute('news', 'url_key')) {
            $this->_grid->addButton('refresh_urlindex', ['label' => Ddm::getTranslate()->___('刷新%sURL索引', Ddm::getTranslate()->translate('新闻')), 'href' => Ddm::getLanguage()->getUrl('*/*/refresh-url-index'), 'onclick' => 'return refreshUrlIndex(this)']);
            $javascrip = 'function refreshUrlIndex(e){' . 'if(!window.refreshUrlIndexWindow)window.refreshUrlIndexWindow = msg_box("' . Ddm::getTranslate('core')->translate('正在刷新URL索引, <br />请勿关闭或刷新浏览器') . '... <span id=\"refresh-url-index-status\"></span>","' . Ddm::getTranslate('core')->___('刷新%sURL索引', Ddm::getTranslate('admin')->translate('新闻')) . '",false);' . 'Ajax().post(typeof e == "string" ? e : e.getAttribute("href"),null,function(xhr,result){' . 'if(result && result.url!=""){$id("refresh-url-index-status").innerHTML = Math.floor((result.from+result.limit)/result.maxId*100)+"%";refreshUrlIndex(result.url);}' . 'else{window.refreshUrlIndexWindow.close();window.refreshUrlIndexWindow = null;msg_box("' . Ddm::getTranslate('core')->translate('已完成') . '","' . Ddm::getTranslate('core')->___('刷新%sURL索引', Ddm::getTranslate('admin')->translate('新闻')) . '");}' . '},"json");' . 'return false;}';
            $this->_grid->addJs($javascrip);
        }
        return $this;
    }

    /**
     * @return int
     */
    public function getLanguageId(): int
    {
        return $this->_languageId === false ? $this->_languageId = (int) Request::get('language', 'int', '0') : $this->_languageId;
    }

    /**
     * @return $this
     */
    protected function _prepareColumns(): static
    {
        $this->getGrid()->addColumn('news_id', ['label' => 'ID', 'width' => 80, 'type' => 'number', 'field_name' => 'news_id', 'column' => 'main_table.news_id', 'sort' => true]);
        if (Ddm::getHelper('core')->getEntityAttribute('news_category', 'name')) {
            $this->getGrid()->addColumn('category', ['label' => Ddm::getTranslate('news')->translate('分类'), 'type' => 'text', 'field_name' => 'category_name', 'search' => true, 'edit' => false, 'sort' => false]);
        }
        $this->getGrid()->addColumn('title', ['label' => Ddm::getTranslate('news')->translate('标题'), 'type' => 'text', 'field_name' => 'title', 'search' => true, 'edit' => true, 'sort' => false])->addColumn('author', ['label' => Ddm::getTranslate('news')->translate('作者'), 'type' => 'text', 'field_name' => 'author', 'column' => 'author', 'search' => true, 'edit' => true, 'sort' => false])->addColumn('views', ['label' => Ddm::getTranslate('news')->translate('浏览次数'), 'width' => 80, 'type' => 'number', 'field_name' => 'views', 'column' => 'main_table.views', 'sort' => true, 'edit' => true])->addColumn('create_at', ['label' => Ddm::getTranslate('admin')->translate('增加时间'), 'type' => 'datetime', 'field_name' => 'create_at', 'column' => 'main_table.create_at', 'search' => true, 'sort' => true])->addColumn('update_at', ['label' => Ddm::getTranslate('admin')->translate('最后修改'), 'type' => 'datetime', 'field_name' => 'update_at', 'column' => 'main_table.update_at', 'search' => true, 'sort' => true])->addColumn('action', ['label' => Ddm::getTranslate('admin')->translate('操作'), 'width' => 100, 'type' => 'action', 'actions' => [['label' => Ddm::getTranslate('admin')->translate('修改'), 'url' => Ddm::getLanguage()->getUrl('*/*/edit', ['id' => '{news_id}'])]]]);
        return parent::_prepareColumns();
    }

    protected function _prepareMassaction(): static
    {
        $this->getGrid()->addAction('move_category', ['url' => Ddm::getLanguage()->getUrl('*/*/save-category'), 'label' => Ddm::getTranslate('core')->translate('更改分类')]);
        $this->_addShowNewsCategoriesJs();
        return parent::_prepareMassaction();
    }

    public function applyFilter(array $columnOption, mixed $fieldName, mixed $value): static
    {
        if (strpos($fieldName, '.')) {
            parent::applyFilter($columnOption, $fieldName, $value);
        } elseif ($fieldName == 'category_name') {
            parent::applyFilter($columnOption, $this->getLanguageId() ? new Expression("IF(main_table.category_id='0','-',IFNULL(category2.value,category.value))") : new Expression('IFNULL(category.value,\'-\')'), $value);
        } else {
            list($operator, $value) = $this->_grid->getCondition($columnOption, $value);
            $this->_news->addAttributeToFilter($fieldName, $value, $operator);
        }
        return $this;
    }

    /**
     * @return $this
     */
    protected function _addShowNewsCategoriesJs(): static
    {
        $js = 'var $combobox = false;';
        $js .= 'DOMLoaded(function(){actions.opt.change = function(cb,selected){';
        $js .= 'if(selected[0]=="' . Ddm::getLanguage()->getUrl('*/*/save-category') . '"){$combobox = new combobox({"edit":false,"apply":$id("selectboxinput"+actions.getHash()).parentNode.parentNode,"name":"news_category","zIndex":9999,"data":' . Option::singleton()->toComboboxData() . '});$combobox.init();}';
        $js .= 'else if($combobox){$e_($id("selectboxinput"+$combobox.getHash()).parentNode);$combobox = false;}';
        $js .= '}});';
        $this->getGrid()->addJs($js);
        return $this;
    }
}

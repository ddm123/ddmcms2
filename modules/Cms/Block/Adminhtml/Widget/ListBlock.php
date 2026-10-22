<?php

namespace Cms\Block\Adminhtml\Widget;

use Admin\Block\List\AbstractModel;
use Cms\Block\Adminhtml\Onepage\ListBlock as OnepageListBlock;
use Cms\Model\Widget;
use Ddm;
use Ddm\Request;

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

    /** @var Widget */
    protected mixed $_widget = null;

    /**
     * @return $this
     */
    protected function _initGrid(): static
    {
        $this->_grid = $this->_createGridBlock();
        $this->_grid->titleText = Ddm::getTranslate('cms')->translate('自定义块');
        $this->_grid->defaultSort = 'widget_id';
        //是$columnId, 而不是字段名
        $this->_grid->defaultDir = 'DESC';
        $this->_grid->primaryKey = 'widget_id';
        $this->_grid->saveFieldValueUrl = Ddm::getUrl('*/*/save-field-value');
        $this->_widget = new Widget();
        $this->_widget->setLanguageId($this->getLanguageId())->addAttributeToSelect('title');
        $this->_grid->setSelect($this->_widget->getSelect())->setResetGridSearchUrl(Ddm::getLanguage()->getUrl('*/*', ['language' => $this->getLanguageId()]));
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
        $this->getGrid()->addColumn('widget_id', ['label' => 'ID', 'width' => 80, 'type' => 'number', 'field_name' => 'widget_id', 'column' => 'main_table.widget_id', 'sort' => true])->addColumn('title', ['label' => Ddm::getTranslate('cms')->translate('标题'), 'type' => 'text', 'field_name' => 'title', 'search' => true, 'edit' => false, 'sort' => false])->addColumn('identifier', ['label' => Ddm::getTranslate('cms')->translate('标识符'), 'type' => 'text', 'field_name' => 'identifier', 'column' => 'main_table.identifier', 'search' => true, 'edit' => false, 'sort' => true])->addColumn('create_at', ['label' => Ddm::getTranslate('admin')->translate('增加时间'), 'type' => 'datetime', 'field_name' => 'create_at', 'column' => 'main_table.create_at', 'search' => true, 'sort' => true])->addColumn('update_at', ['label' => Ddm::getTranslate('admin')->translate('最后修改'), 'type' => 'datetime', 'field_name' => 'update_at', 'column' => 'main_table.update_at', 'search' => true, 'sort' => true])->addColumn('action', ['label' => Ddm::getTranslate('admin')->translate('操作'), 'width' => 100, 'type' => 'action', 'actions' => [['label' => Ddm::getTranslate('admin')->translate('修改'), 'url' => Ddm::getLanguage()->getUrl('*/*/edit', ['id' => '{widget_id}'])]]]);
        return parent::_prepareColumns();
    }

    public function applyFilter(array $columnOption, mixed $fieldName, mixed $value): static
    {
        if (strpos($fieldName, '.')) {
            parent::applyFilter($columnOption, $fieldName, $value);
        } else {
            list($operator, $value) = $this->_grid->getCondition($columnOption, $value);
            $this->_widget->addAttributeToFilter($fieldName, $value, $operator);
        }
        return $this;
    }
}

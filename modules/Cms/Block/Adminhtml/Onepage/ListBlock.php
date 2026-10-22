<?php

namespace Cms\Block\Adminhtml\Onepage;

use Admin\Block\List\AbstractModel;
use Cms\Model\Onepage;
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

    /** @var Onepage|null */
    protected mixed $_onepage = null;

    /**
     * @return $this
     */
    protected function _initGrid(): static
    {
        $this->_grid = $this->_createGridBlock();
        $this->_grid->titleText = Ddm::getTranslate('cms')->translate('单页面');
        $this->_grid->defaultSort = 'onepage_id';
        //是$columnId, 而不是字段名
        $this->_grid->defaultDir = 'DESC';
        $this->_grid->primaryKey = 'onepage_id';
        $this->_grid->saveFieldValueUrl = Ddm::getUrl('*/*/save-field-value', ['language' => $this->getLanguageId()]);
        $this->_grid->getFieldValueUrl = Ddm::getUrl('*/*/get-field-value', ['language' => $this->getLanguageId()]);
        $this->_grid->setResetGridSearchUrl(Ddm::getUrl('*/*/index', ['language' => $this->getLanguageId()]));
        $this->_onepage = new Onepage();
        $this->_onepage->setLanguageId($this->getLanguageId())->addAttributeToSelect('title')->addAttributeToSelect('url_key')->addAttributeToSelect('is_enabled');
        $this->setSelect($this->_onepage->getSelect());
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
        $this->getGrid()->addColumn('onepage_id', ['label' => 'ID', 'width' => 80, 'type' => 'number', 'field_name' => 'onepage_id', 'column' => 'main_table.onepage_id', 'sort' => true])->addColumn('title', ['label' => Ddm::getTranslate('cms')->translate('标题'), 'type' => 'text', 'field_name' => 'title', 'search' => true, 'edit' => true, 'sort' => true])->addColumn('url_key', ['label' => Ddm::getTranslate('cms')->translate('URL'), 'type' => 'text', 'field_name' => 'url_key', 'value_callback' => [$this, 'setUrlKeyCallback'], 'search' => true, 'edit' => true, 'sort' => false])->addColumn('is_enabled', ['label' => Ddm::getTranslate('core')->translate('启用'), 'type' => 'bool', 'field_name' => 'is_enabled', 'search' => true, 'edit' => true])->addColumn('create_at', ['label' => Ddm::getTranslate('admin')->translate('增加时间'), 'type' => 'datetime', 'field_name' => 'create_at', 'column' => 'main_table.create_at', 'search' => true, 'sort' => true])->addColumn('update_at', ['label' => Ddm::getTranslate('admin')->translate('最后修改'), 'type' => 'datetime', 'field_name' => 'update_at', 'column' => 'main_table.update_at', 'search' => true, 'sort' => true])->addColumn('action', ['label' => Ddm::getTranslate('admin')->translate('操作'), 'width' => 100, 'type' => 'action', 'actions' => [['label' => Ddm::getTranslate('admin')->translate('修改'), 'url' => Ddm::getLanguage()->getUrl('*/*/edit', ['id' => '{onepage_id}'])]]]);
        return $this;
    }

    public function applyFilter(array $columnOption, mixed $fieldName, mixed $value): static
    {
        if (strpos($fieldName, '.')) {
            parent::applyFilter($columnOption, $fieldName, $value);
        } else {
            list($operator, $value) = $this->_grid->getCondition($columnOption, $value);
            $this->_onepage->addAttributeToFilter($fieldName, $value, $operator);
        }
        return $this;
    }

    protected function _prepareMassaction(): static
    {
        $this->getGrid()->addAction('active', ['url' => Ddm::getLanguage()->getUrl('*/*/active', ['value' => 1]), 'label' => Ddm::getTranslate('core')->translate('启用')])->addAction('inactive', ['url' => Ddm::getLanguage()->getUrl('*/*/active', ['value' => 0]), 'label' => Ddm::getTranslate('core')->translate('禁用')]);
        return parent::_prepareMassaction();
    }

    /**
     * @param array $row
     * @param array $column
     * @return string
     */
    public function setUrlKeyCallback(array $row, array $column): string
    {
        return '<a href="' . Ddm::getLanguage($this->getLanguageId())->getBaseUrl() . $row[$column['field_name']] . '" target="_brank">' . $row[$column['field_name']] . ' ^</a>';
    }
}

<?php

namespace Language\Block\Adminhtml;

use Admin\Block\List\AbstractModel;
use Admin\Model\Admin;
use Ddm;
use Ddm\Db;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class ListBlock extends AbstractModel
{

    /**
     * @return $this
     */
    protected function _initGrid(): static
    {
        $this->_grid = $this->_createGridBlock();
        $this->_grid->titleText = Ddm::getTranslate('language')->translate('网站语言');
        $this->_grid->defaultSort = 'position';
        //是$columnId, 而不是字段名
        $this->_grid->defaultDir = 'ASC';
        $this->_grid->primaryKey = 'language_id';
        $this->_grid->saveFieldValueUrl = Ddm::getUrl('*/*/save-field-value');
        $select = Db::table(['a' => 'language']);
        $this->_grid->setSelect($select);
        return $this;
    }

    /**
     * @return $this
     */
    protected function _prepareColumns(): static
    {
        $actions = [['label' => Ddm::getTranslate('admin')->translate('修改'), 'url' => Ddm::getLanguage()->getUrl('*/*/edit', ['id' => '{language_id}'])], ['label' => Ddm::getTranslate('admin')->translate('删除'), 'url' => Ddm::getLanguage()->getUrl('*/*/delete', ['id' => '{language_id}']), 'onclick' => 'deleteLanguage(this,event)']];
        if (Admin::loggedInAdmin()->isAllow('language/language', 'translate')) {
            $actions[] = ['label' => Ddm::getTranslate('language')->translate('翻译'), 'url' => Ddm::getLanguage()->getUrl('*/adminhtml-translate', ['id' => '{language_id}'])];
        }
        $this->getGrid()->addColumn('id', ['label' => 'ID', 'width' => 80, 'type' => 'number', 'field_name' => 'language_id', 'column' => 'a.language_id', 'sort' => true])->addColumn('code', ['label' => Ddm::getTranslate('language')->translate('语言代码'), 'type' => 'text', 'field_name' => 'language_code', 'column' => 'a.language_code', 'search' => true, 'edit' => true, 'sort' => true])->addColumn('name', ['label' => Ddm::getTranslate('core')->translate('名称'), 'type' => 'text', 'field_name' => 'language_name', 'column' => 'a.language_name', 'search' => true, 'edit' => true, 'sort' => true])->addColumn('enable', ['label' => Ddm::getTranslate('core')->translate('启用'), 'type' => 'bool', 'field_name' => 'is_enable', 'column' => 'a.is_enable', 'search' => true, 'edit' => true])->addColumn('position', ['label' => Ddm::getTranslate('core')->translate('位置'), 'type' => 'number', 'field_name' => 'position', 'column' => 'a.position', 'edit' => true, 'sort' => true])->addColumn('action', ['label' => Ddm::getTranslate('admin')->translate('操作'), 'width' => 160, 'type' => 'action', 'actions' => $actions]);
        $this->getGrid()->addJs('function deleteLanguage(o,ev){doane(ev);' . 'msg_box("' . Ddm::getTranslate('language')->translate('该操作不可撤消，确定要删除该语言？') . '<br />' . Ddm::getTranslate('language')->translate('注：这也将会删除该语言的全部翻译') . '",' . '"' . Ddm::getTranslate('core')->translate('提示') . '",{' . '"' . Ddm::getTranslate('core')->translate('是') . '":function(){this.window.close();window.location.href = o.href;},' . '"' . Ddm::getTranslate('core')->translate('否') . '":function(){this.window.close();}' . '});}');
        return $this;
    }

    protected function _prepareMassaction(): static
    {
        parent::_prepareMassaction();
        $this->getGrid()->removeAction('delete');
        return $this;
    }
}

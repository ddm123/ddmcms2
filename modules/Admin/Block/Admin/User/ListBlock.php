<?php

namespace Admin\Block\Admin\User;

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

    protected int $_loggedInAdminId = 0;

    /**
     * @return $this
     */
    protected function _initGrid(): static
    {
        $this->_grid = $this->_createGridBlock();
        $this->_grid->titleText = Ddm::getTranslate('admin')->translate('管理员');
        $this->_grid->defaultSort = 'id';
        //是$columnId, 而不是字段名
        $this->_grid->primaryKey = 'admin_id';
        $this->_grid->getFieldValueUrl = Ddm::getLanguage()->getUrl('*/*/get-field-value');
        $this->_grid->saveFieldValueUrl = Ddm::getLanguage()->getUrl('*/*/save-field-value');
        $select = Db::table(['a' => 'admin_user'])->select(['a.*']);
        $this->_grid->setSelect($select);
        $this->_loggedInAdminId = Admin::loggedInAdmin()->getId();
        $this->_grid->setDisableRowCallback([$this, 'isSelf']);
        return $this;
    }

    /**
     * @return $this
     */
    protected function _prepareColumns(): static
    {
        $this->getGrid()->addColumn('id', ['label' => 'ID', 'width' => 80, 'type' => 'number', 'field_name' => 'admin_id', 'column' => 'a.admin_id', 'sort' => true])->addColumn('name', ['label' => Ddm::getTranslate('admin')->translate('用户名'), 'type' => 'text', 'field_name' => 'admin_name', 'column' => 'a.admin_name', 'search' => true, 'edit' => true, 'sort' => true])->addColumn('description', ['label' => Ddm::getTranslate('admin')->translate('描述'), 'field_name' => 'description', 'column' => 'a.description', 'search' => true, 'edit' => true, 'sort' => true])->addColumn('loginnum', ['label' => Ddm::getTranslate('admin')->translate('登录次数'), 'width' => 80, 'type' => 'number', 'field_name' => 'loginnum', 'column' => 'a.loginnum', 'search' => true, 'edit' => true, 'sort' => true])->addColumn('logintime', ['label' => Ddm::getTranslate('admin')->translate('最后登录'), 'type' => 'datetime', 'field_name' => 'logintime', 'column' => 'a.logintime', 'search' => true, 'sort' => true])->addColumn('is_active', ['label' => Ddm::getTranslate('core')->translate('启用'), 'type' => 'bool', 'field_name' => 'is_active', 'column' => 'a.is_active', 'search' => true, 'edit' => true])->addColumn('edit_name', ['label' => Ddm::getTranslate('admin')->translate('允许修改用户名'), 'type' => 'bool', 'field_name' => 'edit_name', 'column' => 'a.edit_name', 'search' => true, 'edit' => true])->addColumn('edit_pass', ['label' => Ddm::getTranslate('admin')->translate('允许修改密码'), 'type' => 'bool', 'field_name' => 'edit_pass', 'column' => 'a.edit_pass', 'search' => true, 'edit' => true])->addColumn('action', ['label' => Ddm::getTranslate('admin')->translate('操作'), 'width' => 100, 'type' => 'action', 'actions' => [['label' => Ddm::getTranslate('admin')->translate('修改'), 'url' => Ddm::getLanguage()->getUrl('*/*/edit', ['id' => '{admin_id}'])]]]);
        return $this;
    }

    protected function _prepareMassaction(): static
    {
        $this->getGrid()->addAction('active', ['url' => Ddm::getLanguage()->getUrl('*/*/active', ['value' => 1]), 'label' => Ddm::getTranslate('core')->translate('启用')])->addAction('inactive', ['url' => Ddm::getLanguage()->getUrl('*/*/active', ['value' => 0]), 'label' => Ddm::getTranslate('core')->translate('禁用')]);
        return parent::_prepareMassaction();
    }

    /**
     * @param array $row
     * @return bool
     */
    public function isSelf(array $row): bool
    {
        return $this->_loggedInAdminId == $row['admin_id'];
    }
}

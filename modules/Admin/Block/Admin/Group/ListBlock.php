<?php

namespace Admin\Block\Admin\Group;

use Admin\Block\List\AbstractModel;
use Admin\Model\Admin;
use Core\Block\AbstractModel as BlockAbstractModel;
use Ddm;
use Ddm\Db;
use Ddm\Db\Expression;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class ListBlock extends AbstractModel
{

    protected array $_loggedInAdminGroups = [];

    /**
     * @return $this
     */
    protected function _initGrid(): static
    {
        $this->_grid = $this->_createGridBlock();
        $this->_grid->titleText = Ddm::getTranslate('admin')->translate('管理员组');
        $this->_grid->primaryKey = 'group_id';
        $this->_grid->defaultSort = 'group_id';
        $this->_grid->getFieldValueUrl = '';
        $this->_grid->saveFieldValueUrl = Ddm::getLanguage()->getUrl('*/*/save-field-value');
        $select = Db::table(['g' => 'admin_group']);
        $this->_grid->setSelect($select);
        $this->_loggedInAdminGroups = Admin::loggedInAdmin()->getGroups();
        $this->_grid->setDisableRowCallback([$this, 'isSelf']);
        return $this;
    }

    /**
     * @return $this
     */
    protected function _prepareColumns(): static
    {
        $this->getGrid()->addColumn('group_id', ['label' => 'ID', 'width' => 80, 'type' => 'number', 'field_name' => 'group_id', 'column' => 'g.group_id', 'sort' => true])->addColumn('group_name', ['label' => Ddm::getTranslate('admin')->translate('组名称'), 'field_name' => 'group_name', 'column' => 'g.group_name', 'search' => true, 'edit' => true, 'sort' => true])->addColumn('description', ['label' => Ddm::getTranslate('admin')->translate('描述'), 'field_name' => 'description', 'column' => 'g.description', 'search' => true, 'edit' => true, 'sort' => true])->addColumn('count', ['label' => Ddm::getTranslate('admin')->translate('用户数'), 'width' => 80, 'type' => 'number', 'field_name' => 'count'])->addColumn('action', ['label' => Ddm::getTranslate('admin')->translate('操作'), 'width' => 100, 'type' => 'action', 'actions' => [['label' => Ddm::getTranslate('admin')->translate('修改'), 'url' => Ddm::getLanguage()->getUrl('*/*/edit', ['id' => '{group_id}'])]]]);
        return $this;
    }

    /**
     * @return $this
     */
    protected function _addCount(): static
    {
        if ($listData = $this->getGrid()->getListData()) {
            $groupIds = [];
            foreach ($listData as $row) {
                $groupIds[] = $row['group_id'];
            }
            $select = Db::table(['g' => 'admin_group']);
            $select->join(['gu' => 'admin_group_user'], 'gu.group_id', '=', 'g.group_id');
            $select->groupBy('g.group_id')->whereIn('g.group_id', $groupIds);
            $groupCounts = $select->get(['g.group_id', 'count' => new Expression('COUNT(*)')], 'group_id');
            foreach ($listData as $key => $row) {
                $listData[$key]['count'] = isset($groupCounts[$row['group_id']]) ? $groupCounts[$row['group_id']]['count'] : 0;
            }
            $this->getGrid()->setListData($listData);
        }
        return $this;
    }

    /**
     * @param array $row
     * @return bool
     */
    public function isSelf(array $row): bool
    {
        return isset($this->_loggedInAdminGroups[$row['group_id']]);
    }

    protected function _beforeToHtml(): static
    {
        parent::_beforeToHtml();
        $this->_addCount();
        return $this;
    }
}

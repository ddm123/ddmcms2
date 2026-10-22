<?php

namespace Admin\Controller;

use Admin\Model\Admin;
use Admin\Model\Group as ModelGroup;
use Ddm;
use Ddm\Db;
use Ddm\Request;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Group extends AbstractModel
{

    public function indexAction(): void
    {
        $this->_getTemplate()->setTitle(Ddm::getTranslate('admin')->___('管理员组'))->addWindowScript()->addBlock($this->_createBlock('admin_group_list'), 'admin_grouplist')->setActiveMemu('admin')->display();
    }

    public function addAction(): void
    {
        $this->_getTemplate()->setTitle(Ddm::getTranslate('admin')->___('增加管理员组'))->addBlock($this->_createBlock('admin_group_edit'), 'add_admin_group')->setActiveMemu('admin')->display();
    }

    public function editAction(): void
    {
        $groupId = Request::get('id', 'int');
        $group = new ModelGroup();
        if (!$groupId || !$group->load($groupId)->getId()) {
            $this->getNotice()->addError(Ddm::getTranslate('admin')->___('您修改的%s不存在', Ddm::getTranslate('admin')->translate('管理员组')));
            Request::redirect(Ddm::getLanguage()->getUrl('*/*'));
            return;
        }
        Ddm::register('current_group', $group);
        $this->_getTemplate()->setTitle(Ddm::getTranslate('admin')->___('修改管理员组'))->addBlock($this->_createBlock('admin_group_edit'), 'edit_admin_group')->setActiveMemu('admin')->display();
    }

    public function saveFieldValueAction(): void
    {
        $fields = ['group_name', 'description'];
        $field = Request::post('f');
        $id = (int) Request::post('id');
        $value = trim(Request::post('v', false, ''));
        $success = 'true';
        $message = '';
        $group = new ModelGroup();
        if ($this->isAllowed(ModelGroup::ALLOW_TYPE_EDIT) && $id && $group->load($id)->getId() == $id) {
            Db::beginTransaction();
            try {
                if ($field == 'group_name') {
                    if ($value == '') {
                        $success = 'false';
                        $message = Ddm::getTranslate('admin')->___('%s不能为空', Ddm::getTranslate('admin')->translate('组名称'));
                    } elseif ($group->group_name != $value) {
                        $exists = Db::table($group->getResource()->getMainTable(), true)->where('group_name', '=', $value)->where('group_id', '!=', $id)->exists();
                        if ($exists) {
                            $success = 'false';
                            $message = Ddm::getTranslate('admin')->translate('您填写的组名称已经存在了');
                        } else {
                            $group->setData($field, $value)->save();
                            $success = 'true';
                            $message = '';
                        }
                    }
                } elseif (in_array($field, $fields)) {
                    $group->setData($field, $value)->save();
                }
                Db::commit();
            } catch (\Exception $ex) {
                Db::rollBack();
                $success = 'false';
                $message = $ex->getMessage();
            }
        } else {
            $success = 'false';
            $message = Ddm::getTranslate('admin')->___('您没有%s的权限', Ddm::getTranslate('admin')->translate('修改管理员组'));
        }
        echo '{"success":' . $success . ',"value":"' . addslashes($value) . '","message":"' . addslashes($message) . '"}';
    }

    public function saveAction(): void
    {
        $groupId = Request::get('id', 'int');
        $groupName = trim(Request::post('group_name', false, ''));
        $allowType = (int) Request::post('allow_type');
        $resources = Request::post('resources', false, '');
        $gotoUrl = Request::server()->HTTP_REFERER ? Request::server()->HTTP_REFERER : Ddm::getLanguage()->getUrl('*/*');
        if ($groupName == '') {
            $this->getNotice()->addError(Ddm::getTranslate('admin')->___('%s不能为空', Ddm::getTranslate('admin')->translate('组名称')));
        } elseif ($allowType == 0 && !$resources) {
            $this->getNotice()->addError(Ddm::getTranslate('admin')->translate('管理员组不能没有任何的权限'));
        } elseif ($groupId && !$this->isAllowed(ModelGroup::ALLOW_TYPE_EDIT)) {
            $this->getNotice()->addError(Ddm::getTranslate('admin')->___('您没有%s的权限', Ddm::getTranslate('admin')->translate('修改管理员组')));
        } elseif (!$groupId && !$this->isAllowed(ModelGroup::ALLOW_TYPE_ADD)) {
            $this->getNotice()->addError(Ddm::getTranslate('admin')->___('您没有%s的权限', Ddm::getTranslate('admin')->translate('增加管理员组')));
        } else {
            $group = new ModelGroup();
            if ($groupId) {
                $group->load($groupId);
                if (!$group->getId()) {
                    $this->getNotice()->addError(Ddm::getTranslate('admin')->___('您修改的%s不存在', Ddm::getTranslate('admin')->translate('管理员组')));
                    Request::redirect(Ddm::getLanguage()->getUrl('*/*'));
                    return;
                }
            }
            $exists = Db::table($group->getResource()->getMainTable(), true)->where('group_name', '=', $groupName)->where('group_id', $groupId ? '!=' : '>', $groupId ?: 0)->exists();
            if ($exists) {
                $this->getNotice()->addError(Ddm::getTranslate('admin')->translate('您填写的组名称已经存在了'));
                Request::redirect($groupId ? Ddm::getLanguage()->getUrl('*/*/edit', ['id' => $groupId]) : Ddm::getLanguage()->getUrl('*/*/add'));
                return;
            }
            Db::beginTransaction();
            try {
                $group->addData(['group_name' => $groupName, 'description' => Request::post('description', false, '')])->save()->saveResources($allowType == '1' ? 'all' : $resources);
                Db::commit();
                $this->getNotice()->addSuccess(Ddm::getTranslate('admin')->___('%s已保存成功', Ddm::getTranslate('admin')->translate('管理员组')));
                $gotoUrl = Request::get('back') == 'edit' ? Ddm::getLanguage()->getUrl('*/*/edit', ['id' => $group->getId()]) : Ddm::getLanguage()->getUrl('*/*');
            } catch (\Exception $ex) {
                Db::rollBack();
                $this->getNotice()->addError($ex->getMessage());
            }
        }
        Request::redirect($gotoUrl);
    }

    public function deleteAction(): void
    {
        if ($ids = Request::post('ids', 'int', null)) {
            Db::beginTransaction();
            try {
                $loggedInAdminGroups = Admin::loggedInAdmin()->getGroups();
                $i = 0;
                foreach ($ids as $id) {
                    if (isset($loggedInAdminGroups[$id])) {
                        $this->getNotice()->addNotice(Ddm::getTranslate('admin')->___('不能删除自己所隶属的管理员组：%s', $loggedInAdminGroups[$id]));
                    } else {
                        $group = new ModelGroup();
                        $group->setId($id)->delete();
                        $i++;
                    }
                }
                if ($i) {
                    $this->getNotice()->addSuccess(Ddm::getTranslate('admin')->translate('已经成功删除选择的记录'));
                }
                Db::commit();
            } catch (\Exception $ex) {
                Db::rollBack();
                $this->getNotice()->addError($ex->getMessage());
            }
        } else {
            $this->getNotice()->addError(Ddm::getTranslate('admin')->translate('没有选择到任何记录'));
        }
        Request::redirect(Ddm::getLanguage()->getUrl('*/*'));
    }

    public function isAllowed(string $actionName): bool
    {
        return $this->_isAllowed($actionName, 'admin/admin_group');
    }
}

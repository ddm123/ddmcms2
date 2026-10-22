<?php

namespace Admin\Controller;

use Admin\Model\Admin as ModelAdmin;
use Admin\Model\Group;
use Ddm;
use Ddm\Cache;
use Ddm\Db;
use Ddm\Request;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Admin extends AbstractModel
{

    public function indexAction(): void
    {
        $this->_getTemplate()->setTitle(Ddm::getTranslate('admin')->___('管理员'))->addWindowScript()->addBlock($this->_createBlock('admin_user_list'), 'admin_userlist')->setActiveMemu('admin')->display();
    }

    public function addAction(): void
    {
        $this->_getTemplate()->setTitle(Ddm::getTranslate('admin')->___('增加管理员'))->addBlock($this->_createBlock('admin_user_edit'), 'add_admin_user')->addJs('ToolMan.js', 'core')->setActiveMemu('admin')->display();
    }

    public function editAction(): void
    {
        $adminId = Request::get('id', 'int');
        $admin = new ModelAdmin();
        if (!$adminId || !$admin->load($adminId)->getId()) {
            $this->getNotice()->addError(Ddm::getTranslate('admin')->___('您修改的%s不存在', Ddm::getTranslate('admin')->translate('管理员')));
            Request::redirect(Ddm::getLanguage()->getUrl('*/*'));
            return;
        }
        Ddm::register('current_admin', $admin);
        if ($adminId == ModelAdmin::loggedInAdmin()->getId()) {
            $this->_getTemplate()->getBlock('header')->getBlock('notice')->getNotice()->addNotice(Ddm::getTranslate('admin')->___('注: 如果你需要更改用户名或密码, 建议到 %s 修改, 在这里更改密码你将会被强制退出登录', '<a href="' . Ddm::getUrl('admin/admin/profile') . '">' . Ddm::getTranslate('admin')->___('我的资料') . '</a>'));
        }
        $this->_getTemplate()->setTitle(Ddm::getTranslate('admin')->___('修改管理员'))->addBlock($this->_createBlock('admin_user_edit'), 'edit_admin_user')->addJs('ToolMan.js', 'core')->setActiveMemu('admin')->display();
    }

    public function profileAction(): void
    {
        $this->_getTemplate()->setTitle(Ddm::getTranslate('admin')->___('我的资料'))->addBlock($this->_createBlock('admin_user_profile'), 'admin_user_profile')->setActiveMemu('admin')->display();
    }

    public function getFieldValueAction(): void
    {
        $fields = ['admin_name', 'loginnum', 'is_active', 'edit_name', 'edit_pass'];
        $field = Request::post('f');
        $id = (int) Request::post('id');
        if ($id && in_array($field, $fields)) {
            echo Db::table('admin_user')->where('admin_id', '=', $id)->value($field);
        } else {
            echo trim(Request::post('v', false, ''));
        }
    }

    public function saveFieldValueAction(): void
    {
        $fields = ['admin_name', 'loginnum', 'is_active', 'edit_name', 'edit_pass', 'description'];
        $field = Request::post('f');
        $id = (int) Request::post('id');
        $value = trim(Request::post('v', false, ''));
        $success = 'true';
        $message = '';
        $admin = new ModelAdmin();
        if ($this->isAllowed('edit') && $id && $admin->load($id)->getId() == $id) {
            Db::beginTransaction();
            try {
                if ($field == 'admin_name') {
                    if ($value == '') {
                        $success = 'false';
                        $message = Ddm::getTranslate('admin')->___('%s不能为空', Ddm::getTranslate('admin')->translate('用户名'));
                    } else {
                        $exists = Db::table($admin->getResource()->getMainTable(), true)->where('admin_name', '=', $value)->where('admin_id', '!=', $id)->exists();
                        if ($exists) {
                            $success = 'false';
                            $message = Ddm::getTranslate('admin')->translate('您填写的用户名已经存在了');
                        } else {
                            $admin->setData($field, $value);
                            $admin->save();
                            $success = 'true';
                        }
                    }
                } elseif (in_array($field, $fields)) {
                    $field == 'description' or $value = (int) $value;
                    $admin->setData($field, $value);
                    $admin->save();
                    $success = 'true';
                }
                Db::commit();
            } catch (\Exception $ex) {
                Db::rollBack();
                $success = 'false';
                $message = $ex->getMessage();
            }
        } else {
            $success = 'false';
            $message = Ddm::getTranslate('admin')->___('您没有%s的权限', Ddm::getTranslate('admin')->translate('修改管理员'));
        }
        echo '{"success":' . $success . ',"value":"' . addslashes($value) . '","message":"' . addslashes($message) . '"}';
    }

    public function activeAction(): void
    {
        if ($ids = Request::post('ids', 'int', null)) {
            if ($this->isAllowed('edit')) {
                Db::beginTransaction();
                try {
                    $value = (int) Request::get('value');
                    Db::table('admin_user')->where('admin_id', $ids)->update(['is_active' => $value]);
                    $this->getNotice()->addSuccess(Ddm::getTranslate('admin')->___('%s已保存成功', Ddm::getTranslate('admin')->translate('管理员')));
                    Db::commit();
                } catch (\Exception $ex) {
                    Db::rollBack();
                    $this->getNotice()->addError($ex->getMessage());
                }
            } else {
                $this->getNotice()->addError(Ddm::getTranslate('admin')->___('您没有%s的权限', Ddm::getTranslate('admin')->translate('修改管理员')));
            }
        } else {
            $this->getNotice()->addError(Ddm::getTranslate('admin')->translate('没有选择到任何记录'));
        }
        Request::redirect(Ddm::getLanguage()->getUrl('*/*'));
    }

    public function saveAction(): void
    {
        $adminId = (int) Request::get('id', false, 0);
        $adminName = trim(Request::post('username', false, ''));
        $passWord = trim(Request::post('password', false, ''));
        $passWord2 = trim(Request::post('password2', false, ''));
        $groups = Request::post('groups', true);
        $gotoUrl = Request::server()->HTTP_REFERER ? Request::server()->HTTP_REFERER : Ddm::getLanguage()->getUrl('*/*');
        if ($adminName == '') {
            $this->getNotice()->addError(Ddm::getTranslate('admin')->___('%s不能为空', Ddm::getTranslate('admin')->translate('用户名')));
        } elseif (!$adminId && $passWord == '') {
            $this->getNotice()->addError(Ddm::getTranslate('admin')->___('%s不能为空', Ddm::getTranslate('admin')->translate('密码')));
        } elseif ($passWord != $passWord2) {
            $this->getNotice()->addError(Ddm::getTranslate('admin')->translate('两次输入的密码不一致'));
        } elseif (empty($groups)) {
            $this->getNotice()->addError(Ddm::getTranslate('admin')->translate('至少要选择一个管理员组'));
        } elseif ($adminId && !$this->isAllowed(Group::ALLOW_TYPE_EDIT)) {
            $this->getNotice()->addError(Ddm::getTranslate('admin')->___('您没有%s的权限', Ddm::getTranslate('admin')->translate('修改管理员')));
        } elseif (!$adminId && !$this->isAllowed(Group::ALLOW_TYPE_ADD)) {
            $this->getNotice()->addError(Ddm::getTranslate('admin')->___('您没有%s的权限', Ddm::getTranslate('admin')->translate('增加管理员')));
        } else {
            $admin = new ModelAdmin();
            if ($adminId) {
                $admin->load($adminId);
                if (!$admin->getId()) {
                    $this->getNotice()->addError(Ddm::getTranslate('admin')->___('您修改的%s不存在', Ddm::getTranslate('admin')->translate('管理员')));
                    Request::redirect(Ddm::getLanguage()->getUrl('*/*'));
                    return;
                }
            }
            $exists = Db::table($admin->getResource()->getMainTable(), true)->where('admin_name', '=', $adminName)->where('admin_id', $adminId ? '!=' : '>', $adminId ?: 0)->exists();
            if ($exists) {
                $this->getNotice()->addError(Ddm::getTranslate('admin')->translate('您填写的用户名已经存在了'));
                Request::redirect($adminId ? Ddm::getLanguage()->getUrl('*/*/edit', ['id' => $adminId]) : Ddm::getLanguage()->getUrl('*/*/add'));
                return;
            }
            Db::beginTransaction();
            try {
                $admin->addData(['admin_name' => $adminName, 'admin_pass' => $passWord, 'description' => Request::post('description', false, ''), 'groups_position' => $groups, 'loginnum' => (int) Request::post('loginnum'), 'is_active' => (int) Request::post('is_active'), 'edit_name' => (int) Request::post('edit_name'), 'edit_pass' => (int) Request::post('edit_pass')])->save();
                Db::commit();
                $this->getNotice()->addSuccess(Ddm::getTranslate('admin')->___('%s已保存成功', Ddm::getTranslate('admin')->translate('管理员')));
                $gotoUrl = Request::get('back') == 'edit' ? Ddm::getLanguage()->getUrl('*/*/edit', ['id' => $admin->getId()]) : Ddm::getLanguage()->getUrl('*/*');
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
                $adminId = ModelAdmin::loggedInAdmin()->getId();
                $i = 0;
                foreach ($ids as $id) {
                    if ($id == $adminId) {
                        $this->getNotice()->addNotice(Ddm::getTranslate('admin')->___('不能删除自己：%s', ModelAdmin::loggedInAdmin()->admin_name));
                    } else {
                        $admin = new ModelAdmin();
                        $admin->setId($id)->delete();
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

    public function saveProfileAction(): void
    {
        $admin = ModelAdmin::loggedInAdmin();
        $saveAdmin = new ModelAdmin();
        $saveAdmin->load($admin->getId());
        if ($saveAdmin->getId()) {
            $error = false;
            if ($admin->edit_name) {
                if ($adminName = trim(Request::post('username', false, ''))) {
                    $exists = Db::table($saveAdmin->getResource()->getMainTable(), true)->where('admin_name', '=', $adminName)->where('admin_id', '!=', $saveAdmin->getId())->exists();
                    if ($exists) {
                        $this->getNotice()->addError(Ddm::getTranslate('admin')->translate('您填写的用户名已经存在了'));
                        $error = true;
                    } else {
                        $saveAdmin->admin_name = $adminName;
                    }
                }
            }
            $oldPassword = trim(Request::post('oldpassword', false, ''));
            if (!$error && $admin->edit_pass && $oldPassword != '') {
                $password = trim(Request::post('password', false, ''));
                $password2 = trim(Request::post('password2', false, ''));
                if ($password != $password2) {
                    $this->getNotice()->addError(Ddm::getTranslate('admin')->translate('两次输入的密码不一致'));
                    $error = true;
                } elseif ($oldPassword && $admin->getPasswordHash($oldPassword) == $admin->admin_pass) {
                    $saveAdmin->admin_pass = $password;
                } else {
                    $this->getNotice()->addError(Ddm::getTranslate('admin')->translate('旧密码错误'));
                    $error = true;
                }
            }
            if (!$error) {
                $saveAdmin->setExtra('use_timezone', Request::post('use_timezone', false, ''));
                $saveAdmin->save();
                if (isset($adminName) && $adminName != '' && $adminName != $admin->admin_name || isset($password) && $password != '') {
                    $saveAdmin->loginById($admin->getId());
                }
                $this->getNotice()->addSuccess(Ddm::getTranslate('admin')->___('%s已保存成功', Ddm::getTranslate('admin')->translate('管理员')));
            }
        }
        Request::redirect(Ddm::getLanguage()->getUrl('admin/admin/profile'));
    }

    public function loginAction(): void
    {
        if (ModelAdmin::loggedInAdmin()->isLoggedIn()) {
            Request::redirect(Ddm::getLanguage()->getUrl('admin'));
        }
        if (isset($_POST['username']) && isset($_POST['password'])) {
            if (!$this->_validateFormKey()) {
                echo Ddm::getTranslate('admin')->translate('请刷新页面再重试登录');
            } elseif ($result = ModelAdmin::loggedInAdmin()->login($_POST['username'], $_POST['password'])) {
                if ($result === 1) {
                    //删除缓存
                    Cache::singleton()->removeByTags(['admin_user']);
                    echo 'success';
                } else {
                    echo Ddm::getTranslate('admin')->translate('该帐户已被禁用');
                }
            } else {
                echo Ddm::getTranslate('admin')->translate('用户名或密码错误');
            }
        } else {
            parent::loginAction();
        }
    }

    public function logoutAction(): void
    {
        ModelAdmin::loggedInAdmin()->logOut();
        if ($httpRefere = Request::server()->HTTP_REFERER) {
            if (stripos($httpRefere, Ddm::getLanguage()->getUrl('*/*')) !== 0) {
                $httpRefere = null;
            }
        }
        Request::redirect($httpRefere ? $httpRefere : Ddm::getLanguage()->getUrl('admin'));
    }

    public function isAllowed(string $actionName): bool
    {
        if ($actionName == 'profileAction' || $actionName == 'saveProfileAction') {
            return true;
        }
        return $this->_isAllowed($actionName, 'admin/admin_user');
    }
}

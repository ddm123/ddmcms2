<?php

namespace Admin\Controller;

use Admin\Block\Template;
use Admin\Model\Admin;
use Admin\Model\Group;
use Admin\Model\Notice;
use Core\Controller\AbstractModel as ControllerAbstractModel;
use Ddm;
use Ddm\Controller;
use Ddm\Session;

abstract class AbstractModel extends ControllerAbstractModel
{

    protected array $_allow_types = [Group::ALLOW_TYPE_READ => true, Group::ALLOW_TYPE_ADD => true, Group::ALLOW_TYPE_EDIT => true, Group::ALLOW_TYPE_DELETE => true];

    public function __construct()
    {
        parent::__construct();
        //如果未登录
        if (!Admin::loggedInAdmin()->isLoggedIn()) {
            Controller::singleton()->setActionName('login');
        } elseif ($timezone = Admin::loggedInAdmin()->getExtra('use_timezone')) {
            Ddm::getHelper('core')->getDateTime()->setTimezone($timezone);
        }
        Ddm::addEventListener('controller_call_noaction_before', [$this, 'noAction']);
    }

    /**
     * @return Session
     */
    protected function _getSession(): Session
    {
        return Ddm::getHelper('admin')->getSession();
    }

    /**
     * @return Template
     */
    protected function _getTemplate(): Template
    {
        return Template::singleton();
    }

    /**
     * 获取当前登录的管理员
     * @return Admin
     */
    protected function _getAdminUser(): Admin
    {
        return Admin::loggedInAdmin();
    }

    /**
     * @param string $actionName
     * @param string $path
     * @return bool
     */
    protected function _isAllowed(string $actionName, string $path): bool
    {
        if (Controller::singleton()->getActionName() == 'login' || Controller::singleton()->getActionName() == 'logout') {
            return true;
        }
        $type = preg_replace('/Action$/', '', $actionName);
        if ($type == 'save') {
            return Admin::loggedInAdmin()->isAllow($path, Group::ALLOW_TYPE_ADD) || Admin::loggedInAdmin()->isAllow($path, Group::ALLOW_TYPE_EDIT);
        }
        isset($this->_allow_types[$type]) or $type = Group::ALLOW_TYPE_READ;
        return Admin::loggedInAdmin()->isAllow($path, $type);
    }

    /**
     * @return Notice
     */
    public function getNotice(): Notice
    {
        return $this->_notice === null ? $this->_notice = new Notice(false) : $this->_notice;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function addAllowType(string $type): static
    {
        if ($type === null) {
            unset($this->_allow_types[$type]);
        } else {
            $this->_allow_types[$type] = true;
        }
        return $this;
    }

    /**
     * @param Controller $controller
     * @return $this
     */
    public function noPermission(Controller $controller): static
    {
        $this->_getTemplate()->setTitle(Ddm::getTranslate('admin')->___('无权限，访问被禁止'))->addBlock($this->_createBlock('nopermission', 'admin'), 'admin_nopermission')->display();
        return $this;
    }

    /**
     * @param array $params
     * @return $this
     */
    public function noAction(array $params): static
    {
        $params['controller']->set404PageHtml('');
        $this->_getTemplate()->setTitle(Ddm::getTranslate('admin')->translate('你访问的页面不存在'))->addBlock($this->_createBlock('notfound', 'admin'), 'admin_notfound')->display();
        return $this;
    }

    public function loginAction(): void
    {
        $block = $this->_createBlock('admin_login', 'admin');
        /* @var $block \Admin\Block\Admin\Login */
        if (Admin::loggedInAdmin()->verifyCookie() == 2) {
            $block->setLoginNotice('<span style="color:#FF0000;">' . Ddm::getTranslate('admin')->translate('您的帐号已被另一个人在其它地方登录，您被强制退出') . '</span>');
        }
        $this->_getTemplate()->clear()->addBlock($this->_createBlock('head', 'core')->isFetch(false), 'head')->addBlock($block, 'login')->setBlocksLock(true)->setTitle(Ddm::getTranslate('admin')->translate('请填写您的用户名和密码登录'))->addCss('common.css', 'core')->addCss('admin.css', 'admin')->addJs('common.js', 'core')->display();
    }
}

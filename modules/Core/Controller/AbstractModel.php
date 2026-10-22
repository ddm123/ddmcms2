<?php

namespace Core\Controller;

use Core\Block\AbstractModel as BlockAbstractModel;
use Core\Block\Template;
use Ddm;
use Ddm\Controller;
use Ddm\Notice;
use Ddm\Request;

abstract class AbstractModel
{

    protected mixed $_notice = null;

    public function __construct()
    {
    }

    /**
     * @return Template
     */
    protected function _getTemplate(): Template
    {
        return Template::singleton();
    }

    /**
     * @return bool
     */
    protected function _validateFormKey(): bool
    {
        if (isset($_POST['form_key_value']) && $_POST['form_key_value'] === '' || isset($_GET['form_key_value']) && $_GET['form_key_value'] === '') {
            $formKey = Request::post('form_key') or $formKey = Request::get('form_key');
            return $formKey && $formKey === Ddm::getHelper('core')->getFormKey();
        }
        return false;
    }

    /**
     * @return Notice
     */
    public function getNotice(): Notice
    {
        return $this->_notice === null ? $this->_notice = new Notice(false) : $this->_notice;
    }

    /**
     * @param string $blockClass
     * @param string $moduleName
     * @param string $templateName
     * @return BlockAbstractModel
     */
    protected function _createBlock(string $blockClass, ?string $moduleName = null, ?string $templateName = null): BlockAbstractModel
    {
        return $this->_getTemplate()->createBlock($moduleName === null ? Controller::singleton()->getModuleName() : $moduleName, $blockClass, $templateName);
    }

    public function indexAction(): void
    {
        //如果是空Action
    }

    /**
     * @param string $actionName
     * @return boolean
     */
    public function isAllowed(string $actionName): bool
    {
        return true;
    }

    /**
     * @param Controller $controller
     * @return $this
     */
    public function noPermission(Controller $controller): static
    {
        Controller::noPermissionAction();
        return $this;
    }

    /**
     * @param Controller $controller
     * @return $this
     */
    public function callActionBefore(Controller $controller): static
    {
        return $this;
    }

    /**
     * @param Controller $controller
     * @param string $actionName
     * @return $this
     */
    public function callActionAfter(Controller $controller, string $actionName): static
    {
        if ($this->_notice !== null) {
            $this->_notice->save();
        }
        return $this;
    }
}

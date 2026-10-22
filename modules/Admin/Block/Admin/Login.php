<?php

namespace Admin\Block\Admin;

use Core\Block\AbstractModel;
use Ddm;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Login extends AbstractModel
{

    protected string $_loginNotice = '';

    public function init(): static
    {
        parent::init();
        $this->template = 'login.phtml';
        $this->_loginNotice = Ddm::getTranslate('admin')->translate('请填写您的用户名和密码登录');
        return $this;
    }

    public function getLoginUrl(): mixed
    {
        return Ddm::getLanguage()->getUrl('admin/admin/login');
    }

    /**
     * @return string
     */
    public function getLoginNotice(): string
    {
        return $this->_loginNotice;
    }

    /**
     * @param string $string
     * @return $this
     */
    public function setLoginNotice(string $string): static
    {
        $this->_loginNotice = $string;
        return $this;
    }
}

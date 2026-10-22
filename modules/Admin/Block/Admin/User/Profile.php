<?php

namespace Admin\Block\Admin\User;

use Admin\Block\Widget\Form;
use Admin\Model\Admin;
use Core\Model\Date\Timezone;
use Ddm;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Profile extends Form
{

    private mixed $_admin = null;

    /**
     * @return $this
     */
    public function init(): static
    {
        parent::init();
        $this->formAction = Ddm::getLanguage()->getUrl('*/*/save-profile');
        $this->method = 'post';
        $this->formId = 'admin-profile-form';
        $this->titleText = Ddm::getTranslate('admin')->___('我的资料');
        $this->template = 'user/profile_form.phtml';
        $this->removeButton('back');
        return $this;
    }

    /**
     * @return Admin
     */
    public function getAdmin(): Admin
    {
        return $this->_admin === null ? $this->_admin = Admin::loggedInAdmin() : $this->_admin;
    }

    /**
     * @param array $matches
     * @return string
     */
    public function getVarnameCallback(array $matches): string
    {
        return strtoupper($matches[1]);
    }

    /**
     * @return string
     */
    public function timezonesToJson(): string
    {
        $timezones = [['', Ddm::getTranslate('admin')->translate('使用配置的设定')]];
        foreach (Timezone::singleton()->getAllOptions(false) as $option) {
            $timezones[] = [$option['value'], $option['label']];
        }
        return json_encode($timezones);
    }

    public function getUseDateTimezoneSelected(): mixed
    {
        $useTimezone = $this->getAdmin()->getExtra('use_timezone');
        if ($useTimezone && $timezone = Timezone::singleton()->getTimezones($useTimezone)) {
            $useDateTimezone = '["' . $useTimezone . '","' . addslashes($timezone) . '"]';
        } else {
            $useDateTimezone = '["","' . Ddm::getTranslate('admin')->translate('使用配置的设定') . '"]';
        }
        return $useDateTimezone;
    }

    protected function _prepareElements(): static
    {
        $js = '';
        if ($this->getAdmin()->edit_pass) {
            $js .= 'if(form.password.value!=form.password2.value){' . 'form.password2.focus();obj.validateResult = false;' . '$notice(form.password2,{"text":"' . Ddm::getTranslate('admin')->translate('两次输入的密码不一致') . '","direction":"left","icon":"error","close":true});if(event)doane(event);' . '}else{obj.validateResult = true;$notice(form.password2);}' . 'if(!empty(form.password.value) && empty(form.oldpassword.value)){' . 'form.oldpassword.focus();obj.validateResult = false;' . '$notice(form.oldpassword,{"text":"' . Ddm::getTranslate('admin')->___('%s不能为空', Ddm::getTranslate('admin')->translate('旧密码')) . '","direction":"left","icon":"error","close":true});if(event)doane(event);' . '}else{obj.validateResult = true;$notice(form.oldpassword);}';
        }
        if ($js) {
            $this->addValidateFormSuccessJs($js);
        }
        return parent::_prepareElements();
    }
}

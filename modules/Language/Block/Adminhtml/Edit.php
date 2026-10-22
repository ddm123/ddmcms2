<?php

namespace Language\Block\Adminhtml;

use Admin\Block\Widget\Form;
use Core\Model\Attribute\Source\Yesno;
use Ddm;
use Ddm\Db;
use Ddm\Db\Expression;
use Language\Model\Language;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Edit extends Form
{

    /**
     * @return $this
     */
    public function init(): static
    {
        parent::init();
        $this->getLanguage() and $id = $this->getLanguage()->getId() or $id = false;
        $this->formAction = Ddm::getUrl('*/*/save', ['id' => $id]);
        $this->method = 'post';
        $this->formId = 'edit-language-form';
        $this->titleText = $id ? Ddm::getTranslate('language')->translate('修改网站语言') : Ddm::getTranslate('language')->translate('增加网站语言');
        $this->addButton('save_continue_edit', ['label' => Ddm::getTranslate('admin')->translate('保存并继续编辑'), 'onclick' => 'saveAndContinueEdit(editLanguageForm)', 'class' => 'btn-primary']);
        return $this;
    }

    /**
     * @return Language
     */
    public function getLanguage(): Language
    {
        return Ddm::registry('language');
    }

    protected function _prepareElements(): static
    {
        if (!$this->getLanguage()) {
            $builder = Db::table('language');
            $position = $builder->value(new Expression('MAX(' . $builder->wrap('position') . ')'));
            $position = $position ? $position + 1 : 1;
        } else {
            $position = $this->getLanguage()->position;
        }
        $this->addElement('language_name', ['label' => Ddm::getTranslate('core')->translate('名称'), 'type' => 'text', 'name' => 'language_name', 'size' => 20, 'maxlength' => 32, 'verify' => 1, 'value' => $this->getLanguage() ? $this->getLanguage()->language_name : ''])->addElement('code', ['label' => Ddm::getTranslate('language')->translate('语言代码'), 'type' => 'text', 'name' => 'code', 'size' => 20, 'maxlength' => 25, 'verify' => '/^\w+$/', 'value' => $this->getLanguage() ? $this->getLanguage()->language_code : '', 'errormsg' => Ddm::getTranslate('core')->translate('仅允许英文字母、数字或下划线的组合，不可以全是数字'), 'notice' => Ddm::getTranslate('core')->translate('仅允许英文字母、数字或下划线的组合，不可以全是数字')])->addElement('enable', ['label' => Ddm::getTranslate('core')->translate('启用'), 'type' => 'radiogroup', 'name' => 'enable', 'value' => $this->getLanguage() && $this->getLanguage()->is_enable ? '1' : '0', 'id' => 'is_enable', 'list' => Yesno::singleton()->getAllOptions(false, false)])->addElement('position', ['label' => Ddm::getTranslate('core')->translate('位置'), 'type' => 'text', 'name' => 'position', 'size' => 10, 'verify' => 1, 'value' => $position]);
        $this->addJs($this->_getJs());
        return parent::_prepareElements();
    }

    /**
     * @return string
     */
    protected function _getJs(): string
    {
        return 'observer(editLanguageForm.form.code,"change",function(ev){' . 'if(editLanguageForm.form.code.value!=""){' . 'Ajax().get("' . Ddm::getUrl('*/*/check-code') . '","id=' . ($this->getLanguage() ? $this->getLanguage()->language_id : '') . '&code="+editLanguageForm.form.code.value,function(XHR,result){' . 'if(result!="0"){editLanguageForm.form.code.focus();$notice(editLanguageForm.form.code,{"text":result,"direction":"left","icon":"error","close":false});}' . 'else{$notice(editLanguageForm.form.code);}' . '},"text");' . '}});';
    }
}

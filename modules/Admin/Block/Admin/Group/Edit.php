<?php

namespace Admin\Block\Admin\Group;

use Admin\Block\Widget\Form;
use Admin\Model\Group;
use Ddm;

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
        $this->getGroup() and $id = $this->getGroup()->getId() or $id = false;
        $this->formAction = Ddm::getLanguage()->getUrl('*/*/save', ['id' => $id]);
        $this->method = 'post';
        $this->formId = 'edit-group-form';
        $this->titleText = $id ? Ddm::getTranslate('admin')->___('修改管理员组') : Ddm::getTranslate('admin')->___('增加管理员组');
        $this->addButton('save_continue_edit', ['label' => Ddm::getTranslate('admin')->translate('保存并继续编辑'), 'onclick' => 'saveAndContinueEdit(editGroupForm)', 'class' => 'btn-primary']);
        return $this;
    }

    /**
     * @return Group
     */
    public function getGroup(): Group
    {
        return Ddm::registry('current_group');
    }

    protected function _prepareElements(): static
    {
        $allowTypeData = [['value' => 0, 'label' => Ddm::getTranslate('core')->translate('定义')], ['value' => 1, 'label' => Ddm::getTranslate('core')->translate('全部')]];
        $this->addElement('group_name', ['label' => Ddm::getTranslate('admin')->translate('组名称'), 'type' => 'text', 'name' => 'group_name', 'size' => 60, 'verify' => 1, 'value' => $this->getGroup() ? $this->getGroup()->group_name : '']);
        $this->addElement('description', ['label' => Ddm::getTranslate('admin')->translate('描述'), 'type' => 'text', 'name' => 'description', 'style' => 'width:99%;', 'maxlength' => 250, 'verify' => null, 'value' => $this->getGroup() ? $this->getGroup()->description : '']);
        $this->addElement('allow_type', ['label' => Ddm::getTranslate('admin')->translate('权限'), 'align_top' => true, 'type' => 'select', 'name' => 'allow_type', 'change' => ['expr' => 'showAllowResources'], 'jsvarname' => 'allowType', 'data' => $allowTypeData, 'after_html' => $this->createBlock('admin', 'admin_group_allowResources')->toHtml(true)]);
        if ($this->getGroup()) {
            $this->updateElement('allow_type', ['selected' => $this->getGroup()->getAllows() == 'all' ? $allowTypeData[1] : $allowTypeData[0]]);
        }
        $this->addJs('
var allowType;
editGroupForm.onsuccess = function(form,event){
    var _allowType = allowType.getSelected();
    if(_allowType && _allowType[0]=="0"){
        var resources = $id("allow-resources").getElementsByTagName("input"),hasChecked = false;
        for(var len = resources.length;len--;){
            if(resources[len].name.indexOf("resources[")===0){
                if(resources[len].checked){
                    hasChecked = true; break;
                }
            }
        }
        if(!hasChecked){
            $notice($id("allow-resources"),{"text":"' . Ddm::getTranslate('admin')->translate('管理员组不能没有任何的权限') . '","direction":"left","icon":"error","close":true});
            if(event)doane(event);
            editGroupForm.validateResult = false;
        }
    }
};');
        return parent::_prepareElements();
    }
}

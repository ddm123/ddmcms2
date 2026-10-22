<?php

namespace Cms\Block\Adminhtml\Widget;

use Admin\Block\Widget\Form;
use Cms\Model\Widget;
use Ddm;
use Ddm\Request;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Edit extends Form
{

    protected bool $_languageId = false;

    /**
     * @return $this
     */
    public function init(): static
    {
        parent::init();
        $this->getWidgetModel() and $id = $this->getWidgetModel()->getId() or $id = false;
        $this->formAction = Ddm::getUrl('*/*/save', ['id' => $id, 'language' => $this->getLanguageId()]);
        $this->method = 'post';
        $this->formId = 'edit-widget-form';
        $this->titleText = $id ? Ddm::getTranslate('cms')->translate('修改自定义块') : Ddm::getTranslate('cms')->translate('增加自定义块');
        $this->addButton('save_continue_edit', ['label' => Ddm::getTranslate('admin')->translate('保存并继续编辑'), 'onclick' => 'saveAndContinueEdit(editWidgetForm)', 'class' => 'btn-primary']);
        if ($id) {
            $this->addLanguageSwitchBlock();
        }
        return $this;
    }

    /**
     * @return int
     */
    public function getLanguageId(): int
    {
        return $this->_languageId === false ? $this->_languageId = (int) Request::get('language', 'int', '0') : $this->_languageId;
    }

    /**
     * @return Widget
     */
    public function getWidgetModel(): Widget
    {
        return Ddm::registry('widget');
    }

    protected function _prepareElements(): static
    {
        $widget = $this->getWidgetModel();
        /* @var $widget \Cms\Model\Widget */
        foreach (Widget::singleton()->getAttributes() as $attribute) {
            if ($attribute->is_visible) {
                $this->addElement($attribute->attribute_code, $this->_getElementConfig($attribute, $widget));
            }
        }
        $this->addJs('observer($id("widget-identifier"),"change",function(e){' . 'var o = e.srcElement || e.target;' . 'if(!empty(o.value)){' . 'Ajax().get("' . Ddm::getUrl('*/*/checkIdentifier') . '",{"identifier":o.value,"id":"' . ($widget ? $widget->getId() : '') . '"},function(XHR,result){' . 'if(result.error){' . 'add_class(o,"form-invalid");$notice(o,{"text":result.message,"direction":"left","icon":"error","close":true});' . '}else{remove_class(o,"form-invalid");$notice(o);}' . '},"json");' . '}' . '});');
        return parent::_prepareElements();
    }

    /**
     * @param array $attribute
     * @param Widget $widget
     * @return array
     */
    protected function _getElementConfig(array $attribute, Widget $widget): array
    {
        $config = ['label' => Ddm::getTranslate('cms')->translate($attribute->frontend_label), 'id' => 'widget-' . $attribute->attribute_code, 'type' => $attribute->frontend_type, 'name' => 'widget[' . $attribute->attribute_code . ']', 'verify' => $attribute->is_required ? 1 : null, 'value' => $widget ? $widget->{$attribute->attribute_code} : '', 'notice' => $attribute->note ? Ddm::getTranslate('cms')->translate($attribute->note) : null, 'style' => $attribute->frontend_type == 'text' || $attribute->frontend_type == 'textarea' ? 'width:99%;' : null];
        if ($attribute->frontend_type == 'radio') {
            if ($attribute->source_model) {
                $config['list'] = call_user_func([$attribute->source_model, 'singleton'])->getAllOptions(false, false);
            }
            $config['type'] = 'radiogroup';
        }
        if ($attribute->attribute_code == 'identifier') {
            $config['verify'] = '/^[\w\-\.]+$/';
            $config['errormsg'] = Ddm::getTranslate('cms')->translate('仅允许英文字母、数字、下划线或减号组合');
        } elseif ($attribute->attribute_code == 'content') {
            $config['rows'] = '20';
            $config['align_top'] = true;
            $config['use_editor'] = true;
        } elseif ($attribute->attribute_code == 'cache_lifetime') {
            $config['list'] = ['60' => ['value' => '60', 'label' => Ddm::getTranslate('cms')->translate('1个小时')], '720' => ['value' => '720', 'label' => Ddm::getTranslate('cms')->translate('12个小时')], '1440' => ['value' => '1440', 'label' => Ddm::getTranslate('cms')->translate('24个小时')], '0' => ['value' => '0', 'label' => Ddm::getTranslate('core')->translate('永远')]];
            $config['edit'] = $config['filter'] = true;
            $config['notice'] = Ddm::getTranslate('cms')->translate('如果不在下拉选项中选择，请填写一个正整数，单位：分钟，不填写则不缓存');
            if (!$widget) {
                $config['value'] = '0';
            } elseif (null === $config['value'] || !isset($config['list'][(string) $config['value']])) {
                $config['selected'] = ['label' => $config['value'], 'value' => $config['value']];
            }
        }
        if (!$attribute->is_global && $this->getLanguageId() && $widget) {
            isset($config['after_html']) or $config['after_html'] = '';
            $config['after_html'] .= ' &nbsp; <input type="checkbox" name="use_default[]" value="' . $attribute->attribute_code . '"';
            if ($widget->isUseDefaultValue($attribute->attribute_code)) {
                $config['after_html'] .= ' checked="checked"';
                $config['disabled'] = 'disabled';
            }
            $config['after_html'] .= ' id="' . $config['id'] . '-usedefault" onclick="setUseDefaultValue(this,\'' . $config['id'] . '\',\'' . $config['type'] . '\')" />';
            $config['after_html'] .= '<label for="' . $config['id'] . '-usedefault">' . Ddm::getTranslate('core')->translate('使用默认值') . '</label>';
        }
        return $config;
    }
}

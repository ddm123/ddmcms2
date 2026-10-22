<?php

namespace Config\Block\Adminhtml;

use Admin\Block\Widget\Form;
use Ddm;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Field extends Form
{

    /**
     * @return $this
     */
    public function init(): static
    {
        parent::init();
        $this->template = 'field.phtml';
        return $this;
    }

    protected function _prepareElements(): static
    {
        $configs = $this->configs;
        $section = $this->section;
        $group = $this->group;
        $groupsData = $this->groups_data;
        $translateModule = isset($groupsData["{$group}@attributes"]) && isset($groupsData["{$group}@attributes"]['module']) ? $groupsData["{$group}@attributes"]['module'] : 'config';
        foreach ($this->fields as $key => $value) {
            if (!empty($value['global']) && $this->setting->getLanguageId()) {
                continue;
            }
            $value['form'] = $this->_prepareFormElement($key, $value['form']);
            if (isset($configs["{$section}/{$group}/{$key}"]['language_value'])) {
                $value['form']['value'] = $configs["{$section}/{$group}/{$key}"]['language_value'];
            } else {
                $value['form']['value'] = isset($configs["{$section}/{$group}/{$key}"]['default_value']) ? $configs["{$section}/{$group}/{$key}"]['default_value'] : '';
                if ($this->setting->getLanguageId()) {
                    $value['form']['disabled'] = 'disabled';
                }
            }
            if (isset($value['form']['notice'])) {
                $value['form']['notice'] = Ddm::getTranslate($translateModule)->translate($value['form']['notice']);
            }
            if (isset($value['form']['errormsg'])) {
                $value['form']['errormsg'] = Ddm::getTranslate($translateModule)->translate($value['form']['errormsg']);
            }
            $element = array_merge(['label' => Ddm::getTranslate($translateModule)->translate($value['label'])], $value['form']);
            $element['name'] = "config[{$section}][{$group}][{$key}]";
            $element['id'] = "config-{$section}-{$group}-{$key}";
            if ($this->setting->getLanguageId()) {
                isset($element['after_html']) or $element['after_html'] = '';
                $element['after_html'] .= ' &nbsp; <input type="checkbox" name="use_default[]" value="' . "{$section}/{$group}/{$key}" . '"';
                if (!isset($configs["{$section}/{$group}/{$key}"]['language_value']) || $configs["{$section}/{$group}/{$key}"]['language_value'] === null) {
                    $element['after_html'] .= ' checked="checked"';
                }
                $element['default_value'] = isset($configs["{$section}/{$group}/{$key}"]['default_value']) ? $configs["{$section}/{$group}/{$key}"]['default_value'] : '';
                $element['after_html'] .= ' id="' . $element['id'] . '-usedefault" onclick="setUseDefaultValue(this,\'' . $element['id'] . '\',\'' . $value['form']['type'] . '\')" />';
                $element['after_html'] .= '<label for="' . $element['id'] . '-usedefault">' . Ddm::getTranslate('core')->translate('使用默认值') . '</label>';
            }
            $this->addElement("{$section}_{$group}_{$key}", $element);
        }
        return parent::_prepareElements();
    }

    /**
     * @param string $key
     * @param array $form
     * @return array
     */
    protected function _prepareFormElement(string $key, array $form): array
    {
        if (!isset($form['type@attributes'])) {
            $form['type@attributes'] = ['module' => 'config'];
        } elseif (!isset($form['type@attributes']['module'])) {
            $form['type@attributes']['module'] = 'config';
        }
        switch ($form['type']) {
            case 'yesno':
                $form['type'] = 'radiogroup';
                $form['list'] = [['label' => Ddm::getTranslate($form['type@attributes']['module'])->translate($form['yes']), 'value' => $form['yes@attributes']['value'], 'id' => "{$this->section}_{$this->group}_{$key}_1"], ['label' => Ddm::getTranslate($form['type@attributes']['module'])->translate($form['no']), 'value' => $form['no@attributes']['value'], 'id' => "{$this->section}_{$this->group}_{$key}_0"]];
                unset($form['yes'], $form['no'], $form['yes@attributes'], $form['no@attributes']);
                break;
            case 'select':
                if (isset($form['source_model']) && isset($form['source_model@attributes']) && isset($form['source_model@attributes']['method'])) {
                    $sourceModel = new $form['source_model']();
                    $form['data'] = isset($form['source_model@attributes']['parameters']) ? call_user_func_array([$sourceModel, $form['source_model@attributes']['method']], $this->_getParameters($form['source_model@attributes']['parameters'], isset($form['source_model@attributes']['delimiter']) ? $form['source_model@attributes']['delimiter'] : ',')) : call_user_func([$sourceModel, $form['source_model@attributes']['method']]);
                    unset($form['source_model'], $form['source_model@attributes']);
                } elseif (isset($form['option'])) {
                    is_array($form['option']) or $form['option'] = [$form['option']];
                    $form['data'] = [];
                    foreach ($form['option'] as $_k => $optionValue) {
                        $value = isset($form['option@attributes']['value']) ? $form['option@attributes']['value'][$_k] : $optionValue;
                        $form['data'][] = ['value' => $value, 'label' => Ddm::getTranslate($form['type@attributes']['module'])->translate($optionValue)];
                    }
                    unset($form['option'], $form['option@attributes']);
                }
                if (isset($this->configs["{$this->section}/{$this->group}/{$key}"]['language_value'])) {
                    $form['selected'] = $this->configs["{$this->section}/{$this->group}/{$key}"]['language_value'];
                } elseif (isset($this->configs["{$this->section}/{$this->group}/{$key}"]['default_value'])) {
                    $form['selected'] = $this->configs["{$this->section}/{$this->group}/{$key}"]['default_value'];
                }
                break;
            case 'radiogroup':
                if (isset($form['source_model']) && isset($form['source_model@attributes']) && isset($form['source_model@attributes']['method'])) {
                    $sourceModel = new $form['source_model']();
                    $form['list'] = isset($form['source_model@attributes']['parameters']) ? call_user_func_array([$sourceModel, $form['source_model@attributes']['method']], $this->_getParameters($form['source_model@attributes']['parameters'], isset($form['source_model@attributes']['delimiter']) ? $form['source_model@attributes']['delimiter'] : ',')) : call_user_func([$sourceModel, $form['source_model@attributes']['method']]);
                    unset($form['source_model'], $form['source_model@attributes']);
                } elseif (isset($form['option'])) {
                    is_array($form['option']) or $form['option'] = [$form['option']];
                    $form['list'] = [];
                    foreach ($form['option'] as $_k => $optionValue) {
                        $value = isset($form['option@attributes']['value']) ? $form['option@attributes']['value'][$_k] : $optionValue;
                        $form['list'][] = ['value' => $value, 'label' => Ddm::getTranslate($form['type@attributes']['module'])->translate($optionValue)];
                    }
                    unset($form['option'], $form['option@attributes']);
                }
                break;
        }
        unset($form['type@attributes']);
        return $form;
    }

    /**
     * @param string $parameters
     * @param string $delimiter
     * @return array
     */
    protected function _getParameters(string $parameters, string $delimiter = ','): array
    {
        if ($parameters) {
            $p = [];
            foreach (explode($delimiter, $parameters) as $param) {
                $p[] = Ddm::getConfig()->getXmlValue($param);
            }
            return $p;
        }
        return [];
    }
}

<?php

namespace Config\Block\Adminhtml;

use Core\Block\AbstractModel;
use Ddm;
use Ddm\Cache;
use Ddm\Request;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Setting extends AbstractModel
{

    protected string $_settingXmlCacheKey = 'setting_xml_data';

    protected mixed $_settingXmlData = null;

    protected bool $_section = false;

    protected bool $_languageId = false;

    /**
     * @return $this
     */
    public function init(): static
    {
        parent::init();
        $this->template = 'setting.phtml';
        return $this;
    }

    /**
     * @return array
     */
    public function getSettingXmlData(): array
    {
        if ($this->_settingXmlData === null) {
            $this->_settingXmlData = Cache::load($this->_settingXmlCacheKey);
            if ($this->_settingXmlData === false) {
                $this->_settingXmlData = [];
                foreach (Ddm::getModuleConfig() as $moduleName => $moduleData) {
                    $file = SITE_ROOT . '/' . MODULES_FOLDER . "/{$moduleName}/etc/system.xml";
                    if (!empty($moduleData['active']) && is_file($file) && $data = Ddm::getConfig()->xmlFileToArray($file)) {
                        $this->_settingXmlData = $this->_mergeSettingXmlData($data, $this->_settingXmlData);
                    }
                }
                $this->_settingXmlData = $this->_sortSettingXmlData($this->_settingXmlData);
                Cache::save($this->_settingXmlCacheKey, $this->_settingXmlData, ['module', 'config'], 0);
            }
        }
        return $this->_settingXmlData;
    }

    /**
     * @return string
     */
    public function getSection(): string
    {
        if ($this->_section === false) {
            $this->_section = Request::get('section', false, '');
            if ($this->_section == '') {
                foreach ($this->getSettingXmlData() as $key => $value) {
                    if (!strpos($key, '@')) {
                        $this->_section = $key;
                        break;
                    }
                }
            }
        }
        return $this->_section;
    }

    /**
     * @return int
     */
    public function getLanguageId(): int
    {
        return $this->_languageId === false ? $this->_languageId = Request::get('language', 'int', '0') : $this->_languageId;
    }

    /**
     * @return array
     */
    public function getSettingFormData(): array
    {
        $this->getSettingXmlData();
        $this->getSection();
        isset($this->_settingXmlData[$this->_section]) or $this->_section = key($this->_settingXmlData);
        return $this->_settingXmlData[$this->_section];
    }

    /**
     * @return array
     */
    public function getSections(): array
    {
        $sections = [];
        $languageId = $this->getLanguageId() or $languageId = '';
        $settingXmlData = $this->getSettingXmlData();
        foreach ($settingXmlData as $key => $data) {
            if (strpos($key, '@')) {
                continue;
            }
            $data['label'] = Ddm::getTranslate(isset($settingXmlData["{$key}@attributes"]['module']) ? $settingXmlData["{$key}@attributes"]['module'] : 'config')->translate($data['label']);
            $sections[$key] = ['label' => $data['label'], 'url' => Ddm::getUrl('*/*/*', ['section' => $key, 'language' => $languageId])];
        }
        return $sections;
    }

    protected function _beforeToHtml(): static
    {
        parent::_beforeToHtml();
        $configs = Ddm::getConfig()->getResource()->getConfigsFromLanguageId($this->getLanguageId());
        $this->addBlock($this->createBlock('language', 'adminhtml_switch')->setQueryVarName('language'), 'language_switch');
        foreach ($this->getSettingFormData() as $key => $value) {
            if ($key == 'groups') {
                foreach ($value as $key1 => $value1) {
                    if (is_array($value1) && isset($value1['fields'])) {
                        $this->addBlock($this->createBlock('Config', 'adminhtml_field')->addData(['section' => $this->_section, 'group' => $key1, 'fields' => $value1['fields'], 'configs' => $configs, 'groups_data' => $value, 'setting' => $this]), $key1);
                    }
                }
            }
        }
        return $this;
    }

    /**
     * @param array $data
     * @param array $settingXmlData
     * @return array
     */
    protected function _mergeSettingXmlData(array $data, array $settingXmlData): array
    {
        foreach ($data as $key => $value) {
            if (strpos($key, '@')) {
                $settingXmlData[$key] = $value;
                continue;
            }
            isset($settingXmlData[$key]) or $settingXmlData[$key] = [];
            foreach ($value as $key1 => $value1) {
                if ($key1 == 'groups') {
                    foreach ($value1 as $key2 => $value2) {
                        if ($key2 == 'fields') {
                            isset($settingXmlData[$key][$key1][$key2]) or $settingXmlData[$key][$key1][$key2] = [];
                            $settingXmlData[$key][$key1][$key2] = array_merge($settingXmlData[$key][$key1][$key2], $value2);
                        } else {
                            $settingXmlData[$key][$key1][$key2] = $value2;
                        }
                    }
                } else {
                    $settingXmlData[$key][$key1] = $value1;
                }
            }
        }
        return $settingXmlData;
    }

    /**
     * @param array $settingXmlData
     * @return array
     */
    protected function _sortSettingXmlData(array $settingXmlData): array
    {
        foreach ($settingXmlData as $key => $value) {
            if (strpos($key, '@')) {
                continue;
            }
            foreach ($value as $key1 => $value1) {
                if ($key1 == 'groups') {
                    foreach ($value1 as $key2 => $value2) {
                        if (is_array($value2) && isset($value2['fields'])) {
                            uasort($settingXmlData[$key]['groups'][$key2]['fields'], [$this, '_sortFunction']);
                        }
                    }
                }
            }
            uasort($settingXmlData[$key]['groups'], [$this, '_sortFunction']);
        }
        uasort($settingXmlData, [$this, '_sortFunction']);
        return $settingXmlData;
    }

    /**
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    protected function _sortFunction(array $a, array $b): int
    {
        return (isset($a['sort']) ? (int) $a['sort'] : 0) - (isset($b['sort']) ? (int) $b['sort'] : 1);
    }
}

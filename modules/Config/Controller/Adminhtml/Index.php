<?php

namespace Config\Controller\Adminhtml;

use Admin\Controller\AbstractModel;
use Admin\Model\Group;
use Config\Model\Config;
use Ddm;
use Ddm\Db;
use Ddm\Request;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Index extends AbstractModel
{

    private bool $_languageId = false;

    /**
     * @return int
     */
    private function getLanguageId(): int
    {
        return $this->_languageId === false ? $this->_languageId = Request::get('language', 'int', '0') : $this->_languageId;
    }

    public function indexAction(): void
    {
        $this->_getTemplate()->addBlock($this->_createBlock('adminhtml_setting'), 'config_setting')->setTitle(Ddm::getTranslate('config')->translate('网站配置'))->setActiveMemu('system')->display();
    }

    public function saveAction(): void
    {
        if (!$this->isAllowed(Group::ALLOW_TYPE_EDIT)) {
            $this->getNotice()->addError(Ddm::getTranslate('admin')->___('您没有%s的权限', Ddm::getTranslate('config')->translate('修改网站配置')));
        } elseif ($configs = $this->_parsePostData(Request::post('config'))) {
            Db::beginTransaction();
            try {
                $this->_saveConfigs(Ddm::getConfig()->arrayToOne($configs));
                Db::commit();
                //清除Config缓存
                Ddm::getConfig()->clearCache();
                Ddm::getLanguage()->clearBaseUrlCache();
                $this->getNotice()->addSuccess(Ddm::getTranslate('admin')->___('%s已保存成功', Ddm::getTranslate('config')->translate('网站配置')));
            } catch (\Exception $ex) {
                Db::rollBack();
                $this->getNotice()->addError($ex->getMessage());
            }
        }
        Request::redirect(Ddm::getUrl('*/*', ['language' => Request::get('language', 'int', false), 'section' => Request::get('section', false, false)]));
    }

    public function isAllowed(string $actionName): bool
    {
        return $this->_isAllowed($actionName, 'config/config');
    }

    /**
     * @param array $paths
     * @return $this
     */
    protected function _saveConfigs(array $paths): static
    {
        $languageId = $this->getLanguageId();
        foreach ($paths as $path => $value) {
            if (preg_match('#^[A-Za-z/_]+$#', $path)) {
                $config = new Config();
                $config->loadFromPath($path);
                $config->addData(['path' => $path, 'values' => [$languageId => $value]]);
                $config->save();
            }
        }
        return $this;
    }

    /**
     * @param array $data
     * @return array
     */
    protected function _parsePostData(array $data): array
    {
        $configs = [];
        $section = Request::get('section', false, false);
        $useDefault = Request::post('use_default', false, '') or $useDefault = [];
        if (!$this->getLanguageId() && !$data || !$section) {
            return $configs;
        }
        if ($this->getLanguageId() && !$data && !$useDefault) {
            return $configs;
        }
        if ($useDefault) {
            $useDefault = array_flip($useDefault);
        }
        $data or $data = [];
        $block = $this->_createBlock('adminhtml_setting');
        /* @var $block \Config\Block\Adminhtml\Setting */
        $settingXmlData = $block->getSettingXmlData();
        if (!isset($settingXmlData[$section])) {
            return $configs;
        }
        foreach ($settingXmlData[$section] as $key => $value) {
            if ($key == 'groups') {
                foreach ($value as $key1 => $value1) {
                    if (is_array($value1) && isset($value1['fields'])) {
                        foreach ($value1['fields'] as $key2 => $value2) {
                            $configs[$section][$key1][$key2] = isset($data[$section][$key1][$key2]) && !isset($useDefault["{$section}/{$key1}/{$key2}"]) ? $data[$section][$key1][$key2] : false;
                        }
                    }
                }
            }
        }
        return $configs;
    }
}

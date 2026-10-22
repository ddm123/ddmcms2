<?php

namespace Language\Block\Adminhtml;

use Core\Block\AbstractModel;
use Ddm;
use Ddm\Request;
use Language\Model\Language;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Translate extends AbstractModel
{

    public const PAGE_SIZE = 100;

    protected mixed $_currentModule = null;

    /**
     * @return $this
     */
    public function init(): static
    {
        parent::init();
        $this->template = 'translate.phtml';
        return $this;
    }

    /**
     * @return string
     */
    public function getCurrentModule(): string
    {
        if ($this->_currentModule === null) {
            $this->_currentModule = Request::get('module', false, false);
            if (!$this->_currentModule || !Ddm::getEnabledModuleConfig($this->_currentModule)) {
                $this->_currentModule = 'Core';
            } else {
                $this->_currentModule = ucfirst($this->_currentModule);
            }
        }
        return $this->_currentModule;
    }

    /**
     * @return Language
     */
    public function getLanguage(): Language
    {
        return $this->getData('language');
    }

    /**
     * @return array
     */
    public function getTranslate(): array
    {
        $file = Ddm::getTranslate()->getCacheFile($this->getLanguage()->language_id, $this->getCurrentModule());
        $_data = is_file($file) ? unserialize(file_get_contents($file)) : [];
        $data = [];
        $index = 0;
        if ($keyWords = trim(Request::get('kw'))) {
            foreach ($_data as $t1 => $t2) {
                if (strpos($t1, $keyWords) !== false || strpos($t2, $keyWords) !== false) {
                    $data[] = [$index, $t1, $t2];
                }
                $index++;
            }
        } else {
            foreach ($_data as $t1 => $t2) {
                $data[] = [$index, $t1, $t2];
                $index++;
            }
        }
        unset($_data);
        if ($totalRows = count($data)) {
            $pl = $this->createBlock('core', 'pagelink');
            /* @var $pageLink \Core\Block\Pagelink */
            list($startRow, $totalPage) = $pl->parseVars(self::PAGE_SIZE, $totalRows);
            $pl->setPageVars(['id' => $this->getLanguage()->language_id, 'module' => $this->getCurrentModule(), '_current' => true]);
            $pl->setTotalPage($totalPage);
            $pl->beforeHtml = Ddm::getTranslate('language')->___('共 %s 条翻译', $totalRows);
            $this->addBlock($pl, 'page_links');
            if ($totalRows > self::PAGE_SIZE) {
                $data = array_slice($data, $startRow, self::PAGE_SIZE);
            }
        }
        return $data;
    }

    /**
     * @return array
     */
    public function getAllModules(): array
    {
        $allModules = [];
        foreach (Ddm::getModuleConfig() as $moduleName => $moduleData) {
            if (!empty($moduleData['active'])) {
                $allModules[$moduleName] = $moduleName;
            }
        }
        return $allModules;
    }
}

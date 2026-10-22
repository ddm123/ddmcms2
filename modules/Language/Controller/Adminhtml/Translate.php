<?php

namespace Language\Controller\Adminhtml;

use Admin\Controller\AbstractModel;
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

    public function indexAction(): void
    {
        if ($languageId = (int) Request::get('id')) {
            $language = new Language();
            $language->load($languageId);
            if ($language->getId() == $languageId) {
                $this->_getTemplate()->addBlock($this->_createBlock('adminhtml_translate')->addData('language', $language), 'translate')->setTitle(Ddm::getTranslate('language')->translate('语言翻译'))->setActiveMemu('system')->display();
                return;
            } else {
                $this->getNotice()->addError(Ddm::getTranslate('admin')->___('您修改的%s不存在', Ddm::getTranslate('language')->translate('网站语言')));
            }
        }
        Request::redirect(Ddm::getUrl('*/adminhtml'));
    }

    public function saveTranslateAction(): void
    {
        $t1 = Request::post('t1') or $t1 = [];
        $t2 = Request::post('t2') or $t2 = [];
        $page = (int) Request::get('p');
        $id = (int) Request::get('id');
        $moduleName = trim(Request::get('m', false, ''));
        $kw = trim(Request::post('kw')) and $kw = 'kw=' . urlencode($kw);
        if ($id && is_array($t1) && is_array($t2) && Ddm::getEnabledModuleConfig($moduleName)) {
            $file = Ddm::getTranslate()->getCacheFile($id, $moduleName);
            $_data = is_file($file) ? unserialize(file_get_contents($file)) : [];
            $data = [];
            $index = 0;
            foreach ($_data as $_t1 => $_t2) {
                if (isset($t1[$index])) {
                    if ($t1[$index] != '') {
                        $data[$t1[$index]] = $t2[$index];
                    }
                } else {
                    $data[$_t1] = $_t2;
                }
                $index++;
            }
            //追加新增的
            $nt1 = Request::post('nt1');
            $nt2 = Request::post('nt2');
            if ($nt1 && $nt2 && is_array($nt1) && is_array($nt2)) {
                foreach ($nt1 as $key => $value) {
                    if ($value != '') {
                        $data[$value] = $nt2[$key];
                    }
                }
            }
            //保存
            Ddm::getHelper('core')->saveFile($file, serialize($data), LOCK_EX);
            $this->getNotice()->addSuccess(Ddm::getTranslate('admin')->___('%s已保存成功', Ddm::getTranslate('language')->translate('语言翻译')));
        }
        Request::redirect(Ddm::getLanguage()->getUrl('*/*', ['id' => $id, 'p' => $page, 'module' => $moduleName, '_query' => $kw ? $kw : null]));
    }

    public function clearTranslateAction(): void
    {
        $page = (int) Request::get('p');
        $id = (int) Request::get('id');
        $m = trim(Request::get('m', false, ''));
        foreach (Ddm::getModuleConfig() as $moduleName => $moduleData) {
            if (!empty($moduleData['active'])) {
                foreach (Ddm::getLanguage()->getAllLanguage(false) as $language) {
                    $file = Ddm::getTranslate()->getCacheFile($language['language_id'], $moduleName);
                    if (is_file($file)) {
                        $data = unserialize(file_get_contents($file));
                        foreach ($data as $t1 => $t2) {
                            if ($t2 == '') {
                                unset($data[$t1]);
                            }
                        }
                        Ddm::getHelper('core')->saveFile($file, serialize($data), LOCK_EX);
                    }
                }
            }
        }
        $this->getNotice()->addSuccess(Ddm::getTranslate('admin')->___('已全部清除完成'));
        Request::redirect(Ddm::getLanguage()->getUrl('*/*', ['id' => $id, 'p' => $page, 'module' => $m]));
    }

    public function isAllowed(string $actionName): bool
    {
        return $this->_isAllowed('translate', 'language/language');
    }
}

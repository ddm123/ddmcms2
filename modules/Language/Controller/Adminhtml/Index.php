<?php

namespace Language\Controller\Adminhtml;

use Admin\Controller\AbstractModel;
use Admin\Model\Group;
use Ddm;
use Ddm\Db;
use Ddm\Request;
use Language\Model\Language;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Index extends AbstractModel
{

    public function indexAction(): void
    {
        $this->_getTemplate()->addBlock($this->_createBlock('adminhtml_list'), 'language_list')->setTitle(Ddm::getTranslate('language')->translate('网站语言'))->setActiveMemu('system')->addWindowScript()->display();
    }

    public function addAction(): void
    {
        $this->_getTemplate()->addBlock($this->_createBlock('adminhtml_edit'), 'add_language')->setTitle(Ddm::getTranslate('language')->translate('增加网站语言'))->setActiveMemu('system')->display();
    }

    public function editAction(): void
    {
        $languageId = Request::get('id', 'int');
        $language = new Language();
        if (!$languageId || !$language->load($languageId)->getId()) {
            $this->getNotice()->addError(Ddm::getTranslate('admin')->___('您修改的%s不存在', Ddm::getTranslate('language')->translate('网站语言')));
            Request::redirect(Ddm::getLanguage()->getUrl('*/*'));
            return;
        }
        Ddm::register('language', $language);
        $this->_getTemplate()->addBlock($this->_createBlock('adminhtml_edit'), 'edit_language')->setTitle(Ddm::getTranslate('language')->translate('修改网站语言'))->setActiveMemu('system')->display();
    }

    public function checkCodeAction(): void
    {
        $output = '1';
        $languageId = (int) Request::get('id');
        if ($languageCode = Request::get('code', '/^\w+$/', '')) {
            if (is_numeric($languageCode)) {
                $output = Ddm::getTranslate('core')->translate('仅允许英文字母、数字或下划线的组合，不可以全是数字');
            } else {
                $output = $this->_codeIsExist($languageCode, $languageId) ? Ddm::getTranslate('language')->translate('你填写的语言代码已经存在') : '0';
            }
        }
        echo $output;
    }

    public function saveFieldValueAction(): void
    {
        $fields = ['language_code' => Ddm::getTranslate('language')->translate('语言代码'), 'language_name' => Ddm::getTranslate('core')->translate('名称'), 'is_enable' => Ddm::getTranslate('core')->translate('启用'), 'position' => Ddm::getTranslate('core')->translate('位置')];
        $field = Request::post('f');
        $id = (int) Request::post('id');
        $value = trim(Request::post('v', false, ''));
        $success = 'true';
        $message = '';
        $language = new Language();
        if ($this->isAllowed('edit') && $id && $language->load($id)->getId() == $id) {
            Db::beginTransaction();
            try {
                if ($field == 'language_code' || $field == 'language_name') {
                    if ($value == '') {
                        $success = 'false';
                        $message = Ddm::getTranslate('admin')->___('%s不能为空', $fields[$field]);
                    } elseif ($field == 'language_code' && (is_numeric($value) || !preg_match('/^\w+$/', $value))) {
                        $success = 'false';
                        $message = Ddm::getTranslate('core')->translate('仅允许英文字母、数字或下划线的组合，不可以全是数字');
                    } elseif ($field == 'language_code' && $this->_codeIsExist($value, $id)) {
                        $success = 'false';
                        $message = Ddm::getTranslate('language')->translate('你填写的语言代码已经存在');
                    } else {
                        $language->setData($field, $value)->save();
                        $success = 'true';
                    }
                } elseif (isset($fields[$field])) {
                    $value = (int) $value;
                    $language->setData($field, $value)->save();
                    $success = 'true';
                }
                Db::commit();
            } catch (\Exception $ex) {
                Db::rollBack();
                $success = 'false';
                $message = $ex->getMessage();
            }
        } else {
            $success = 'false';
            $message = Ddm::getTranslate('admin')->___('您没有%s的权限', Ddm::getTranslate('language')->translate('修改网站语言'));
        }
        echo '{"success":' . $success . ',"value":"' . addslashes($value) . '","message":"' . addslashes($message) . '"}';
    }

    public function saveAction(): void
    {
        $languageId = (int) Request::get('id');
        $languageCode = trim(Request::post('code', false, ''));
        $languageName = trim(Request::post('language_name', false, ''));
        $gotoUrl = Request::server()->HTTP_REFERER ? Request::server()->HTTP_REFERER : Ddm::getUrl('*/*');
        if ($languageCode == '') {
            $this->getNotice()->addError(Ddm::getTranslate('admin')->___('%s不能为空', Ddm::getTranslate('language')->translate('语言代码')));
        } elseif ($languageName == '') {
            $this->getNotice()->addError(Ddm::getTranslate('admin')->___('%s不能为空', Ddm::getTranslate('core')->translate('名称')));
        } elseif (is_numeric($languageCode) || !preg_match('/^\w+$/', $languageCode)) {
            $this->getNotice()->addError(Ddm::getTranslate('core')->translate('仅允许英文字母、数字或下划线的组合，不可以全是数字'));
        } elseif ($this->_codeIsExist($languageCode, $languageId)) {
            $this->getNotice()->addError(Ddm::getTranslate('language')->translate('你填写的语言代码已经存在'));
        } elseif ($languageId && !$this->isAllowed(Group::ALLOW_TYPE_EDIT)) {
            $this->getNotice()->addError(Ddm::getTranslate('admin')->___('您没有%s的权限', Ddm::getTranslate('language')->translate('修改网站语言')));
        } elseif (!$languageId && !$this->isAllowed(Group::ALLOW_TYPE_ADD)) {
            $this->getNotice()->addError(Ddm::getTranslate('admin')->___('您没有%s的权限', Ddm::getTranslate('language')->translate('增加网站语言')));
        } else {
            $language = new Language();
            if ($languageId) {
                $language->load($languageId);
                if (!$language->getId()) {
                    $this->getNotice()->addError(Ddm::getTranslate('admin')->___('您修改的%s不存在', Ddm::getTranslate('language')->translate('网站语言')));
                    Request::redirect(Ddm::getUrl('*/*'));
                    return;
                }
            }
            Db::beginTransaction();
            try {
                $language->addData(['language_code' => $languageCode, 'language_name' => $languageName, 'is_enable' => (int) Request::post('enable'), 'position' => (int) Request::post('position')])->save();
                Db::commit();
                $this->getNotice()->addSuccess(Ddm::getTranslate('admin')->___('%s已保存成功', Ddm::getTranslate('language')->translate('网站语言')));
                $gotoUrl = Request::get('back') == 'edit' ? Ddm::getUrl('*/*/edit', ['id' => $language->getId()]) : Ddm::getUrl('*/*');
            } catch (\Exception $ex) {
                Db::rollBack();
                $this->getNotice()->addError($ex->getMessage());
            }
        }
        Request::redirect($gotoUrl);
    }

    public function deleteAction(): void
    {
        if ($id = Request::get('id', 'int', null)) {
            Db::beginTransaction();
            try {
                $language = new Language();
                $language->load($id);
                if ($language->getId()) {
                    $language->delete();
                    $this->getNotice()->addSuccess(Ddm::getTranslate('admin')->translate('已经成功删除'));
                }
                Db::commit();
            } catch (\Exception $ex) {
                Db::rollBack();
                $this->getNotice()->addError($ex->getMessage());
            }
        }
        Request::redirect(Ddm::getUrl('*/*'));
    }

    public function saveTranslateAction(): void
    {
        if ($languageId = (int) Request::get('id')) {
            if ($this->isAllowed('translate')) {
                $language = new Language();
                $language->load($languageId);
                if ($language->getId() == $languageId) {
                    echo 'Save success';
                    return;
                } else {
                    $this->getNotice()->addError(Ddm::getTranslate('admin')->___('您修改的%s不存在', Ddm::getTranslate('language')->translate('网站语言')));
                }
            } else {
                $this->getNotice()->addError(Ddm::getTranslate('admin')->___('您没有%s的权限', Ddm::getTranslate('language')->translate('修改语言翻译')));
            }
        }
        Request::redirect(Ddm::getUrl('*/*'));
    }

    /**
     * @param string $languageCode
     * @param int $languageId
     * @return bool
     */
    protected function _codeIsExist(string $languageCode, ?int $languageId = null): bool
    {
        $result = false;
        $defaultLanguage = Ddm::getLanguage()->getDefaultLanguage(false);
        if (strtolower($defaultLanguage['language_code']) == strtolower($languageCode)) {
            $result = true;
        } else {
            $builder = Db::table('language');
            $builder->where('language_code', '=', $languageCode);
            if ($languageId) {
                $builder->where('language_id', '!=', $languageId);
            }
            $result = (bool) $builder->value('language_id');
        }
        return $result;
    }

    public function isAllowed(string $actionName): bool
    {
        return $this->_isAllowed($actionName, 'language/language');
    }
}

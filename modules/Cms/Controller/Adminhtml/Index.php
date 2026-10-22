<?php

namespace Cms\Controller\Adminhtml;

use Admin\Controller\AbstractModel;
use Admin\Model\Group;
use Cms\Model\Onepage;
use Ddm;
use Ddm\Db;
use Ddm\Db\Builder;
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

    protected bool $_languageId = false;

    /**
     * @return int
     */
    protected function _getLanguageId(): int
    {
        return $this->_languageId === false ? $this->_languageId = (int) Request::get('language', 'int', '0') : $this->_languageId;
    }

    public function indexAction(): void
    {
        $this->_getTemplate()->addBlock($this->_createBlock('adminhtml_onepage_list'), 'onepage_list')->setTitle(Ddm::getTranslate('cms')->translate('单页面'))->setActiveMemu('cms')->addWindowScript()->display();
    }

    public function addAction(): void
    {
        $this->_getTemplate()->addBlock($this->_createBlock('adminhtml_onepage_edit'), 'add_onepage')->setTitle(Ddm::getTranslate('cms')->translate('增加单页面'))->setActiveMemu('cms')->display();
    }

    public function editAction(): void
    {
        $onepageId = Request::get('id', 'int');
        $onepage = new Onepage();
        $onepage->setLanguageId($this->_getLanguageId());
        if (!$onepageId || !$onepage->load($onepageId)->getId()) {
            $this->getNotice()->addError(Ddm::getTranslate('admin')->___('您修改的%s不存在', Ddm::getTranslate('cms')->translate('单页面')));
            Request::redirect(Ddm::getLanguage()->getUrl('*/*'));
            return;
        }
        Ddm::register('onepage', $onepage);
        $this->_getTemplate()->addBlock($this->_createBlock('adminhtml_onepage_edit'), 'edit_onepage')->setTitle(Ddm::getTranslate('cms')->translate('修改单页面'))->setActiveMemu('cms')->display();
    }

    public function getFieldValueAction(): void
    {
        $field = Request::post('f');
        $id = (int) Request::post('id');
        if ($id && $value = Onepage::singleton()->setId($id)->getAttributeValue($field, $this->_getLanguageId())) {
            echo $value;
        } else {
            echo trim(Request::post('v', false, ''));
        }
    }

    public function saveFieldValueAction(): void
    {
        $field = Request::post('f');
        $id = (int) Request::post('id');
        $value = trim(Request::post('v', false, ''));
        $success = 'true';
        $message = '';
        $onepage = new Onepage();
        $onepage->setLanguageId($this->_getLanguageId());
        if ($this->isAllowed('edit') && $id && $onepage->load($id)->getId() == $id) {
            Db::beginTransaction();
            try {
                if ($field == 'is_enabled') {
                    $value = (int) $value;
                }
                $onepage->setData($field, $value)->save();
                $success = 'true';
                Db::commit();
            } catch (\Exception $ex) {
                Db::rollBack();
                $success = 'false';
                $message = $ex->getMessage();
            }
        } else {
            $success = 'false';
            $message = Ddm::getTranslate('admin')->___('您没有%s的权限', Ddm::getTranslate('cms')->translate('修改单页面'));
        }
        if ($field == 'url_key') {
            $value = '<a href="' . Ddm::getLanguage($this->_getLanguageId())->getBaseUrl() . $value . '" target="_brank">' . $value . ' ^</a>';
        }
        echo '{"success":' . $success . ',"value":"' . addslashes($value) . '","message":"' . addslashes($message) . '"}';
    }

    public function activeAction(): void
    {
        if ($ids = Request::post('ids', 'int', null)) {
            if ($this->isAllowed('edit')) {
                Db::beginTransaction();
                try {
                    $value = (int) Request::get('value');
                    $isEnabledAttribute = Ddm::getHelper('core')->getEntityAttribute('onepage', 'is_enabled');
                    if ($isEnabledAttribute) {
                        if ($this->_getLanguageId()) {
                            Db::table($isEnabledAttribute->getTable(), true)->where('entity_id', $ids)->where('attribute_id', '=', $isEnabledAttribute->getId())->where('language_id', '=', $this->_getLanguageId())->update(['value' => $value, 'is_use_default' => 0]);
                        } else {
                            Db::table($isEnabledAttribute->getTable(), true)->where('entity_id', $ids)->where('attribute_id', '=', $isEnabledAttribute->getId())->where(function (Builder $builder) {
                                $builder->where('language_id', '=', 0)->orWhere('is_use_default', '=', 1);
                            })->update(['value' => $value]);
                        }
                        foreach ($ids as $_id) {
                            Ddm::getHelper('Cms')->removeOnepageCache($_id);
                        }
                        //刷新缓存
                        $this->getNotice()->addSuccess(Ddm::getTranslate('admin')->___('%s已保存成功', Ddm::getTranslate('cms')->translate('单页面')));
                    } else {
                        throw new \Exception(Ddm::getTranslate('core')->___('%s 属性不存在', "'is_enabled'"));
                    }
                    Db::commit();
                } catch (\Exception $ex) {
                    Db::rollBack();
                    $this->getNotice()->addError($ex->getMessage());
                }
            } else {
                $this->getNotice()->addError(Ddm::getTranslate('admin')->___('您没有%s的权限', Ddm::getTranslate('admin')->translate('修改管理员')));
            }
        } else {
            $this->getNotice()->addError(Ddm::getTranslate('admin')->translate('没有选择到任何记录'));
        }
        Request::redirect(Ddm::getLanguage()->getUrl('*/*'));
    }

    public function saveAction(): void
    {
        $onepageId = (int) Request::get('id');
        $languageId = (int) Request::get('language');
        $gotoUrl = Request::server()->HTTP_REFERER ? Request::server()->HTTP_REFERER : Ddm::getUrl('*/*');
        if ($onepageId && !$this->isAllowed(Group::ALLOW_TYPE_EDIT)) {
            $this->getNotice()->addError(Ddm::getTranslate('admin')->___('您没有%s的权限', Ddm::getTranslate('cms')->translate('修改单页面')));
        } elseif (!$onepageId && !$this->isAllowed(Group::ALLOW_TYPE_ADD)) {
            $this->getNotice()->addError(Ddm::getTranslate('admin')->___('您没有%s的权限', Ddm::getTranslate('cms')->translate('增加单页面')));
        } else {
            $onepage = new Onepage();
            $onepage->setLanguageId($languageId);
            if ($onepageId) {
                $onepage->load($onepageId);
                if (!$onepage->getId()) {
                    $this->getNotice()->addError(Ddm::getTranslate('admin')->___('您修改的%s不存在', Ddm::getTranslate('cms')->translate('单页面')));
                    Request::redirect(Ddm::getUrl('*/*'));
                    return;
                }
            }
            Db::beginTransaction();
            try {
                $onepageData = Request::post('onepage');
                $useDefault = Request::post('use_default');
                if ($useDefault && is_array($useDefault)) {
                    $onepage->setUseDefaultAttribute($useDefault);
                }
                $onepage->addData($onepageData)->save();
                Db::commit();
                $this->getNotice()->addSuccess(Ddm::getTranslate('admin')->___('%s已保存成功', Ddm::getTranslate('cms')->translate('单页面')));
                $gotoUrl = Request::get('back') == 'edit' ? Ddm::getUrl('*/*/edit', ['id' => $onepage->getId(), 'language' => $languageId]) : Ddm::getUrl('*/*');
            } catch (\Exception $ex) {
                Db::rollBack();
                $this->getNotice()->addError($ex->getMessage());
            }
        }
        Request::redirect($gotoUrl);
    }

    public function deleteAction(): void
    {
        if ($ids = Request::post('ids', 'int', null)) {
            Db::beginTransaction();
            try {
                $i = 0;
                foreach ($ids as $id) {
                    $onepage = new Onepage();
                    $onepage->setData('onepage_id', (int) $id)->delete();
                    $i++;
                }
                if ($i) {
                    $this->getNotice()->addSuccess(Ddm::getTranslate('admin')->translate('已经成功删除选择的记录'));
                }
                Db::commit();
            } catch (\Exception $ex) {
                Db::rollBack();
                $this->getNotice()->addError($ex->getMessage());
            }
        } else {
            $this->getNotice()->addError(Ddm::getTranslate('admin')->translate('没有选择到任何记录'));
        }
        Request::redirect(Ddm::getLanguage()->getUrl('*/*'));
    }

    public function isAllowed(string $actionName): bool
    {
        return $this->_isAllowed($actionName, 'cms/onepage');
    }
}

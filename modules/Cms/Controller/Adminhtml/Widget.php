<?php

namespace Cms\Controller\Adminhtml;

use Admin\Controller\AbstractModel;
use Admin\Model\Group;
use Cms\Model\Widget as ModelWidget;
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
class Widget extends AbstractModel
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
        $this->_getTemplate()->addBlock($this->_createBlock('adminhtml_widget_list'), 'widget_list')->setTitle(Ddm::getTranslate('cms')->translate('自定义块'))->setActiveMemu('cms')->addWindowScript()->display();
    }

    public function addAction(): void
    {
        $this->_getTemplate()->addBlock($this->_createBlock('adminhtml_widget_edit'), 'add_widget')->setTitle(Ddm::getTranslate('cms')->translate('增加自定义块'))->setActiveMemu('cms')->display();
    }

    public function editAction(): void
    {
        $widgetId = Request::get('id', 'int');
        $widget = new ModelWidget();
        $widget->setLanguageId($this->_getLanguageId());
        if (!$widgetId || !$widget->load($widgetId)->getId()) {
            $this->getNotice()->addError(Ddm::getTranslate('admin')->___('您修改的%s不存在', Ddm::getTranslate('cms')->translate('自定义块')));
            Request::redirect(Ddm::getLanguage()->getUrl('*/*'));
            return;
        }
        Ddm::register('widget', $widget);
        $this->_getTemplate()->addBlock($this->_createBlock('adminhtml_widget_edit'), 'edit_widget')->setTitle(Ddm::getTranslate('cms')->translate('修改自定义块'))->setActiveMemu('cms')->display();
    }

    public function checkIdentifierAction(): void
    {
        $identifier = trim(Request::get('identifier', false, ''));
        $id = (int) Request::get('id');
        $result = ['error' => 0, 'message' => ''];
        if ($identifier) {
            if (preg_match('/^[\w\-\.\/]+$/', $identifier)) {
                $builder = Db::table('widget');
                $builder->where('identifier', '=', $identifier);
                if ($id) {
                    $builder->where('widget_id', '!=', $id);
                }
                $exists = $builder->exists();
                if ($exists) {
                    $result['error'] = 1;
                    $result['message'] = Ddm::getTranslate('cms')->translate('您填写的标识符已经存在');
                }
            } else {
                $result['error'] = 1;
                $result['message'] = Ddm::getTranslate('cms')->translate('仅允许英文字母、数字、下划线或减号组合');
            }
        }
        echo '{"error":' . $result['error'] . ',"message":"' . addslashes($result['message']) . '"}';
    }

    public function saveAction(): void
    {
        $widgetId = (int) Request::get('id');
        $widgetData = Request::post('widget');
        $useDefault = Request::post('use_default');
        $languageId = (int) Request::get('language');
        $gotoUrl = Request::server()->HTTP_REFERER ? Request::server()->HTTP_REFERER : Ddm::getUrl('*/*');
        $widgetData['identifier'] = isset($widgetData['identifier']) ? trim($widgetData['identifier']) : '';
        if ($widgetId && !$this->isAllowed(Group::ALLOW_TYPE_EDIT)) {
            $this->getNotice()->addError(Ddm::getTranslate('admin')->___('您没有%s的权限', Ddm::getTranslate('cms')->translate('修改自定义块')));
        } elseif (!$widgetId && !$this->isAllowed(Group::ALLOW_TYPE_ADD)) {
            $this->getNotice()->addError(Ddm::getTranslate('admin')->___('您没有%s的权限', Ddm::getTranslate('cms')->translate('增加自定义块')));
        } elseif ($widgetData['identifier'] == '') {
            $this->getNotice()->addError(Ddm::getTranslate('admin')->___('%s不能为空', Ddm::getTranslate('cms')->translate('标识符')));
        } else {
            $widget = new ModelWidget();
            $widget->setLanguageId($languageId);
            if ($widgetId) {
                $widget->load($widgetId);
                if (!$widget->getId()) {
                    $this->getNotice()->addError(Ddm::getTranslate('admin')->___('您修改的%s不存在', Ddm::getTranslate('cms')->translate('自定义块')));
                    Request::redirect(Ddm::getUrl('*/*'));
                    return;
                }
            }
            $exists = Db::table($widget->getResource()->getMainTable(), true)->where('identifier', '=', $widgetData['identifier'])->where('widget_id', $widgetId ? '!=' : '>', $widgetId ? $widgetId : 0)->exists();
            if ($exists) {
                $this->getNotice()->addError(Ddm::getTranslate('cms')->translate('您填写的标识符已经存在'));
                Request::redirect($widgetId ? Ddm::getLanguage()->getUrl('*/*/edit', ['id' => $widgetId, 'language' => $languageId]) : Ddm::getLanguage()->getUrl('*/*/add'));
                return;
            }
            Db::beginTransaction();
            try {
                if ($useDefault && is_array($useDefault)) {
                    $widget->setUseDefaultAttribute($useDefault);
                }
                $widget->addData($widgetData)->save();
                Db::commit();
                $this->getNotice()->addSuccess(Ddm::getTranslate('admin')->___('%s已保存成功', Ddm::getTranslate('cms')->translate('自定义块')));
                $gotoUrl = Request::get('back') == 'edit' ? Ddm::getUrl('*/*/edit', ['id' => $widget->getId(), 'language' => $languageId]) : Ddm::getUrl('*/*');
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
                    $widget = new ModelWidget();
                    $widget->setData('widget_id', (int) $id)->delete();
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
        return $this->_isAllowed($actionName, 'cms/widget');
    }
}

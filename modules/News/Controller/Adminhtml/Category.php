<?php

namespace News\Controller\Adminhtml;

use Admin\Controller\AbstractModel;
use Admin\Model\Group;
use Ddm;
use Ddm\Db;
use Ddm\Db\Expression;
use Ddm\Request;
use News\Model\Category as ModelCategory;
use News\Model\Category\Urlindex;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Category extends AbstractModel
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
        $categoryId = Request::get('id', 'int');
        $category = new ModelCategory();
        $category->setLanguageId($this->_getLanguageId());
        if ($categoryId) {
            if ($category->load($categoryId)->getId()) {
                Ddm::register('category', $category);
            } else {
                $this->getNotice()->addError(Ddm::getTranslate('admin')->___('您修改的%s不存在', Ddm::getTranslate('news')->translate('分类')));
                Request::redirect(Ddm::getLanguage()->getUrl('*/*'));
                return;
            }
        }
        $this->_getTemplate()->addBlock($this->_createBlock('adminhtml_category_edit'), 'edit_category')->setTitle(Ddm::getTranslate('news')->translate('分类'))->setActiveMemu('news')->addWindowScript()->display();
    }

    public function addAction(): void
    {
        $this->indexAction();
    }

    public function editAction(): void
    {
        $this->indexAction();
    }

    public function saveAction(): void
    {
        $categoryId = (int) Request::get('id');
        $languageId = (int) Request::get('language');
        $gotoUrl = Request::server()->HTTP_REFERER ? Request::server()->HTTP_REFERER : Ddm::getUrl('*/*', ['language' => $languageId]);
        if ($categoryId && !$this->isAllowed(Group::ALLOW_TYPE_EDIT)) {
            $this->getNotice()->addError(Ddm::getTranslate('admin')->___('您没有%s的权限', Ddm::getTranslate('news')->translate('修改分类')));
        } elseif (!$categoryId && !$this->isAllowed(Group::ALLOW_TYPE_ADD)) {
            $this->getNotice()->addError(Ddm::getTranslate('admin')->___('您没有%s的权限', Ddm::getTranslate('news')->translate('增加分类')));
        } else {
            $category = new ModelCategory();
            $category->setLanguageId($languageId);
            if ($categoryId) {
                $category->load($categoryId);
                if (!$category->getId()) {
                    $this->getNotice()->addError(Ddm::getTranslate('admin')->___('您修改的%s不存在', Ddm::getTranslate('news')->translate('分类')));
                    Request::redirect(Ddm::getUrl('*/*', ['language' => $languageId]));
                    return;
                }
            }
            Db::beginTransaction();
            try {
                $categoryData = Request::post('category');
                $useDefault = Request::post('use_default');
                if ($useDefault && is_array($useDefault)) {
                    $category->setUseDefaultAttribute($useDefault);
                }
                $category->addData($categoryData)->save();
                Db::commit();
                $this->getNotice()->addSuccess(Ddm::getTranslate('admin')->___('%s已保存成功', Ddm::getTranslate('news')->translate('分类')));
                $gotoUrl = Request::get('back') == 'edit' ? Ddm::getUrl('*/*/edit', ['id' => $category->getId(), 'language' => $languageId]) : Ddm::getUrl('*/*', ['language' => $languageId]);
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
                $category = new ModelCategory();
                $category->load((int) $id)->delete();
                $this->getNotice()->addSuccess(Ddm::getTranslate('admin')->translate('已经成功删除'));
                Db::commit();
            } catch (\Exception $ex) {
                Db::rollBack();
                $this->getNotice()->addError($ex->getMessage());
            }
        }
        Request::redirect(Ddm::getLanguage()->getUrl('*/*'));
    }

    public function refreshUrlIndexAction(): void
    {
        $limit = (int) Request::get('limit') or $limit = 2500;
        $from = (int) Request::get('limit') or $from = 1;
        $urlKeyAttribute = Ddm::getHelper('core')->getEntityAttribute('news_category', 'url_key');
        $maxId = (int) Db::table($urlKeyAttribute->getTable(), true)->where('entity_id', '>', 0)->where('attribute_id', '=', $urlKeyAttribute->getId())->value(new Expression('MAX(entity_id)'));
        $result = ['limit' => $limit, 'from' => $from, 'maxId' => $maxId, 'error' => '', 'url' => ''];
        $urlIndex = new Urlindex();
        if ($from <= $maxId) {
            Db::beginTransaction();
            try {
                $urlIndex->clearUrlIndex();
                $urlIndex->saveUrlIndexFromCategoryId($from, $from + $limit);
                Db::commit();
                if ($from + $limit + 1 <= $maxId) {
                    $result['url'] = Ddm::getUrl('*/*/refresh-url-index', ['limit' => $limit, 'from' => $from + $limit + 1]);
                }
            } catch (\Exception $e) {
                $result['error'] = $e->getMessage();
                Db::rollBack();
            }
        } else {
            $urlIndex->removeClearUrlIndexCache();
        }
        echo json_encode($result);
    }

    public function isAllowed(string $actionName): bool
    {
        return $this->_isAllowed($actionName, 'news/category');
    }
}

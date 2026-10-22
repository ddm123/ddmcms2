<?php

namespace News\Controller\Adminhtml;

use Admin\Controller\AbstractModel;
use Admin\Model\Group;
use Ddm;
use Ddm\Db;
use Ddm\Db\Expression;
use Ddm\Request;
use News\Model\News as ModelNews;
use News\Model\News\Urlindex;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class News extends AbstractModel
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
        $this->_getTemplate()->addBlock($this->_createBlock('adminhtml_news_list'), 'news_list')->setTitle(Ddm::getTranslate('news')->translate('新闻'))->setActiveMemu('news')->addWindowScript()->display();
    }

    public function addAction(): void
    {
        $this->_getTemplate()->addBlock($this->_createBlock('adminhtml_news_edit'), 'add_news')->setTitle(Ddm::getTranslate('news')->translate('增加新闻'))->setActiveMemu('news')->display();
    }

    public function editAction(): void
    {
        $newsId = Request::get('id', 'int');
        $news = new ModelNews();
        $news->setLanguageId($this->_getLanguageId());
        if (!$newsId || !$news->load($newsId)->getId()) {
            $this->getNotice()->addError(Ddm::getTranslate('admin')->___('您修改的%s不存在', Ddm::getTranslate('news')->translate('新闻')));
            Request::redirect(Ddm::getLanguage()->getUrl('*/*'));
            return;
        }
        Ddm::register('news', $news);
        $this->_getTemplate()->addBlock($this->_createBlock('adminhtml_news_edit'), 'edit_news')->setTitle(Ddm::getTranslate('news')->translate('修改新闻'))->setActiveMemu('news')->display();
    }

    public function saveAction(): void
    {
        $newsId = (int) Request::get('id');
        $newsData = Request::post('news');
        $useDefault = Request::post('use_default');
        $languageId = (int) Request::get('language');
        $gotoUrl = Request::server()->HTTP_REFERER ? Request::server()->HTTP_REFERER : Ddm::getUrl('*/*', ['language' => $languageId]);
        if ($newsId && !$this->isAllowed(Group::ALLOW_TYPE_EDIT)) {
            $this->getNotice()->addError(Ddm::getTranslate('admin')->___('您没有%s的权限', Ddm::getTranslate('news')->translate('修改新闻')));
        } elseif (!$newsId && !$this->isAllowed(Group::ALLOW_TYPE_ADD)) {
            $this->getNotice()->addError(Ddm::getTranslate('admin')->___('您没有%s的权限', Ddm::getTranslate('news')->translate('增加新闻')));
        } else {
            $news = new ModelNews();
            $news->setLanguageId($languageId);
            if ($newsId) {
                $news->load($newsId);
                if (!$news->getId()) {
                    $this->getNotice()->addError(Ddm::getTranslate('admin')->___('您修改的%s不存在', Ddm::getTranslate('news')->translate('新闻')));
                    Request::redirect(Ddm::getUrl('*/*'));
                    return;
                }
            }
            Db::beginTransaction();
            try {
                if ($useDefault && is_array($useDefault)) {
                    $news->setUseDefaultAttribute($useDefault);
                }
                $news->addData($newsData)->save();
                Db::commit();
                $this->getNotice()->addSuccess(Ddm::getTranslate('admin')->___('%s已保存成功', Ddm::getTranslate('news')->translate('新闻')));
                $gotoUrl = Request::get('back') == 'edit' ? Ddm::getUrl('*/*/edit', ['id' => $news->getId(), 'language' => $languageId]) : Ddm::getUrl('*/*', ['language' => $languageId]);
            } catch (\Exception $ex) {
                Db::rollBack();
                $this->getNotice()->addError($ex->getMessage());
            }
        }
        Request::redirect($gotoUrl);
    }

    public function saveFieldValueAction(): void
    {
        $field = Request::post('f');
        $id = (int) Request::post('id');
        $value = trim(Request::post('v', false, ''));
        $success = 'true';
        $message = '';
        $news = new ModelNews();
        $news->setLanguageId($this->_getLanguageId());
        if ($this->isAllowed('edit') && $id && $news->load($id)->getId() == $id) {
            Db::beginTransaction();
            try {
                if ($field == 'views') {
                    $value = (int) $value;
                }
                $news->setData($field, $value)->save();
                $success = 'true';
                Db::commit();
            } catch (\Exception $ex) {
                Db::rollBack();
                $success = 'false';
                $message = $ex->getMessage();
            }
        } else {
            $success = 'false';
            $message = Ddm::getTranslate('admin')->___('您没有%s的权限', Ddm::getTranslate('news')->translate('修改新闻'));
        }
        echo '{"success":' . $success . ',"value":"' . addslashes($value) . '","message":"' . addslashes($message) . '"}';
    }

    public function saveCategoryAction(): void
    {
        if ($ids = Request::post('ids', 'int', null)) {
            if (!$this->isAllowed('edit')) {
                $this->getNotice()->addError(Ddm::getTranslate('admin')->___('您没有%s的权限', Ddm::getTranslate('news')->translate('修改新闻')));
            } elseif ($categoryId = (int) Request::post('news_category')) {
                Db::beginTransaction();
                try {
                    $news = new ModelNews();
                    $news->getResource()->changeCategory($categoryId, $ids);
                    Db::commit();
                    $this->getNotice()->addSuccess(Ddm::getTranslate('admin')->___('%s已保存成功', Ddm::getTranslate('news')->translate('新闻')));
                } catch (\Exception $ex) {
                    Db::rollBack();
                    $this->getNotice()->addError($ex->getMessage());
                }
            } else {
                $this->getNotice()->addError(Ddm::getTranslate('admin')->translate('请选择分类'));
            }
        } else {
            $this->getNotice()->addError(Ddm::getTranslate('admin')->translate('没有选择到任何记录'));
        }
        Request::redirect(Ddm::getLanguage()->getUrl('*/*'));
    }

    public function deleteAction(): void
    {
        if ($ids = Request::post('ids', 'int', null)) {
            Db::beginTransaction();
            try {
                $i = 0;
                foreach ($ids as $id) {
                    $news = new ModelNews();
                    $news->setData('news_id', (int) $id)->delete();
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

    public function refreshUrlIndexAction(): void
    {
        $limit = (int) Request::get('limit') or $limit = 2500;
        $from = (int) Request::get('limit') or $from = 1;
        $urlKeyAttribute = Ddm::getHelper('core')->getEntityAttribute('news', 'url_key');
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
        return $this->_isAllowed($actionName, 'news/news');
    }
}

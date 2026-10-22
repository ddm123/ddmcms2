<?php

namespace Admin\Controller;

use Ddm;
use Ddm\Cache as DdmCache;
use Ddm\Request;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Cache extends AbstractModel
{

    public function indexAction(): void
    {
        $this->_getTemplate()->addBlock($this->_createBlock('cache', 'admin'), 'cache')->setTitle(Ddm::getTranslate('admin')->translate('缓存管理'))->setActiveMemu('system')->display();
    }

    public function deleteAction(): void
    {
        $cache = Request::post('cache', '/^[\w,]+$/', null);
        if ($cache && is_array($cache)) {
            foreach ($cache as $tags) {
                if ($tags == 'config') {
                    Ddm::getConfig()->clearCache();
                }
                DdmCache::singleton()->removeByTags(explode(',', $tags));
            }
            $this->getNotice()->addSuccess(Ddm::getTranslate('admin')->translate('已删除选中的缓存'));
        } else {
            $this->getNotice()->addError(Ddm::getTranslate('admin')->translate('至少要选择一个'));
        }
        Request::redirect(Ddm::getUrl('*/cache'));
    }

    public function clearAction(): void
    {
        Ddm::getConfig()->clearCache();
        DdmCache::clear();
        $this->getNotice()->addSuccess(Ddm::getTranslate('admin')->translate('已删除所有的缓存'));
        Request::redirect(Ddm::getUrl('*/cache'));
    }

    public function isAllowed(string $actionName): bool
    {
        return $this->_isAllowed($actionName, 'core/cache');
    }
}

<?php

namespace News\Controller;

use Core\Controller\Frontend\AbstractModel;
use Ddm;
use Ddm\Controller;
use Ddm\Request;
use News\Model\Category as ModelCategory;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Category extends AbstractModel
{

    protected mixed $_currentCategory = null;

    public function indexAction(): void
    {
        Request::redirect(Ddm::getUrl('news'), 302);
    }

    public function viewAction(): void
    {
        if ($this->_initCategory()) {
            $this->_getTemplate()->addBlock($this->_createBlock('news', 'news'), 'news')->display('news');
        } else {
            Controller::noAction();
        }
    }

    /**
     * @return ModelCategory
     */
    protected function _initCategory(): ModelCategory
    {
        if ($this->_currentCategory === null) {
            $this->_currentCategory = false;
            if ($id = (int) Request::get('id')) {
                $category = new ModelCategory();
                $category->setLanguageId(Ddm::getLanguage()->language_id)->load($id);
                if ($category->getId()) {
                    Ddm::register('current_category', $category);
                    $this->_currentCategory = $category;
                }
            }
        }
        return $this->_currentCategory;
    }
}

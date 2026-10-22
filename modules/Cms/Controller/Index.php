<?php

namespace Cms\Controller;

use Core\Controller\Frontend\AbstractModel;
use Ddm;
use Ddm\Controller;
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

    public function indexAction(): void
    {
        Request::singleton()->setParam('id', intval(Ddm::getConfig()->getConfigValue('web/pages/not_found')));
        $this->viewAction();
    }

    public function viewAction(): void
    {
        $block = $this->_createBlock('onepage_view', 'cms');
        /* @var $block \Cms\Block\Onepage\View */
        $block->setTemplateObject($this->_getTemplate());
        if ($onepageData = $block->getOnepageData()) {
            Controller::singleton()->setUrlAlias($onepageData['url_key']);
            if ($onepageData['onepage_id'] == Ddm::getConfig()->getConfigValue('web/pages/home')) {
                Controller::singleton()->setHomePage(true);
            }
            $this->_getTemplate()->addBlock($block, 'view_onepage')->display('cms');
        } else {
            Controller::noAction();
        }
    }
}

<?php

namespace Core\Controller\Frontend;

use Core\Block\Frontend\Template;
use Core\Controller\AbstractModel as ControllerAbstractModel;
use Ddm;
use Ddm\Controller;

abstract class AbstractModel extends ControllerAbstractModel
{

    public function __construct()
    {
        parent::__construct();
        Ddm::addEventListener('controller_call_noaction_before', [$this, 'noAction']);
    }

    /**
     * @return Template
     */
    protected function _getTemplate(): Template
    {
        return Template::singleton();
    }

    /**
     * @return int
     */
    protected function _getNotFoundPageId(): int
    {
        return (int) Ddm::getConfig()->getConfigValue('web/pages/not_found');
    }

    /**
     * @param array $params
     * @return $this
     */
    public function noAction(?array $params = null): static
    {
        if ($onepageId = $this->_getNotFoundPageId()) {
            Ddm::addEventListener('get_onepage_data_before', [$this, '_getOnepageDataBeforeCallbakc']);
            $block = $this->_createBlock('onepage_view', 'cms');
            /* @var $block \Cms\Block\Onepage\View */
            if ($onepageData = $block->getOnepageData()) {
                Controller::singleton()->setUrlAlias($onepageData['url_key']);
                if ($onepageData['onepage_id'] == Ddm::getConfig()->getConfigValue('web/pages/home')) {
                    Controller::singleton()->setHomePage(true);
                }
                if ($params && isset($params['controller'])) {
                    $params['controller']->set404PageHtml('');
                } else {
                    Controller::outputNoActionHeader();
                }
                $this->_getTemplate()->addBlock($block, 'view_onepage')->display('cms');
                return $this;
            }
        }
        if (!$params || !isset($params['controller'])) {
            Controller::noAction();
        }
        return $this;
    }

    /**
     * @param array $params
     * @return $this
     */
    public function _getOnepageDataBeforeCallbakc(array $params): static
    {
        $params['onepage']->setOnepageId($this->_getNotFoundPageId())->setTemplateObject($this->_getTemplate());
        return $this;
    }
}

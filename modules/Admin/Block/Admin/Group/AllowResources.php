<?php

namespace Admin\Block\Admin\Group;

use Admin\Block\Memus;
use Admin\Model\Group;
use Core\Block\AbstractModel;
use Ddm;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class AllowResources extends AbstractModel
{

    private mixed $_memusBlock = null;

    /**
     * @return $this
     */
    public function init(): static
    {
        parent::init();
        $this->template = 'group/allowResources.phtml';
        return $this;
    }

    /**
     * @return Group
     */
    public function getGroup(): Group
    {
        return Ddm::registry('current_group');
    }

    /**
     * @return Memus
     */
    public function getMemusBlock(): Memus
    {
        return $this->_memusBlock === null ? $this->_memusBlock = new Memus() : $this->_memusBlock;
    }

    /**
     * @return array
     */
    public function getMemusData(): array
    {
        return $this->getMemusBlock()->getMemusData();
    }

    protected function _toHtml(): string
    {
        ob_start();
        include $this->getTemplateFile();
        $html = ob_get_clean();
        return $html;
    }

    protected function _beforeToHtml(): static
    {
        parent::_beforeToHtml();
        $this->_cacheId = null;
        return $this;
    }
}

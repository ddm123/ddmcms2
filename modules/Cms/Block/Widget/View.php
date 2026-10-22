<?php

namespace Cms\Block\Widget;

use Cms\Model\Processed\Template;
use Cms\Model\Widget;
use Core\Block\AbstractModel;
use Ddm;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class View extends AbstractModel
{

    protected mixed $_identifier = null;

    public function init(): static
    {
        $this->template = '';
        return parent::init();
    }

    /**
     * @param string $identifier
     * @return $this
     */
    public function setIdentifier(string $identifier): static
    {
        $this->_identifier = $identifier;
        return $this;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->_identifier;
    }

    /**
     * @param string $identifier
     * @return Widget
     */
    public function getWidget(string $identifier): Widget
    {
        $identifier or $identifier = $this->_identifier;
        $data = Ddm::getHelper('cms')->getWidgetCache($identifier);
        $widget = new Widget();
        $widget->setLanguageId(Ddm::getLanguage()->language_id);
        if ($data === false) {
            $widget->loadFromIdentifier($identifier);
            if ($widget->getId()) {
                $template = new Template($widget->content, ['this' => $this, 'widget' => $widget, 'template_object' => $this->getTemplateObject()]);
                $widget->content = $template->processed();
                Ddm::getHelper('cms')->saveWidgetCache($widget);
            }
        } else {
            $widget->addData($data)->setOrigData($data, null, true);
        }
        return $widget;
    }

    protected function _toHtml(): string
    {
        return $this->_identifier ? $this->getWidget($this->_identifier)->content : '';
    }
}

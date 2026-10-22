<?php

namespace Core\Block\Frontend;

use Core\Block\Template as BlockTemplate;
use Ddm;
use Ddm\Notice;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Template extends BlockTemplate
{

    /**
     * 使用单例模式
     * @return \Core\Block\Frontend\Template
     */
    public static function singleton(): Template
    {
        if (self::$_instance === null || !self::$_instance instanceof Template) {
            self::$_instance = new Template();
        }
        return self::$_instance;
    }

    /**
     * @return \Core\Block\Frontend\Breadcrumbs
     */
    public function getBreadcrumb(): Breadcrumbs
    {
        return $this->getBlock('breadcrumbs');
    }

    /**
     * @param string $string
     * @return $this
     */
    public function setTitle(string $string): static
    {
        $this->title = $string . ' - ' . Ddm::getConfig()->getConfigValue('web/base/web_name');
        return $this;
    }

    /**
     * @return $this
     */
    public function addComboboxScript(): static
    {
        $this->addJs('combobox.js', 'core')->addCss('combobox.js.css', 'core');
        return $this;
    }

    /**
     * @return $this
     */
    public function addWindowScript(): static
    {
        $this->addJs('createwin.js', 'core');
        return $this;
    }

    /**
     * @return $this
     */
    public function removeComboboxScript(): static
    {
        $this->removeJs('combobox.js')->removeCss('combobox.js.css');
        return $this;
    }

    /**
     * @return $this
     */
    public function removeWindowScript(): static
    {
        $this->removeJs('createwin.js');
        return $this;
    }

    /**
     * @return $this
     */
    protected function _construct(): static
    {
        parent::_construct();
        $this->title = Ddm::getConfig()->getConfigValue('web/base/web_name');
        $breadcrumb = $this->createBlock('core', 'frontend_breadcrumbs')->addCrumb(['label' => Ddm::getTranslate('core')->translate('首页'), 'link' => Ddm::getHomeUrl()])->isFetch(false);
        $this->addBlock($breadcrumb, 'breadcrumbs');
        $this->addBlock($this->createBlock('core', 'notice')->isFetch(false)->setNotice(new Notice(true)), 'notice');
        return $this;
    }
}

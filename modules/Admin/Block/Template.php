<?php

namespace Admin\Block;

use Admin\Model\Notice;
use Core\Block\Template as BlockTemplate;
use Ddm;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Template extends BlockTemplate
{

    public mixed $activeMemu = null;

    /**
     * 使用单例模式
     * @return \Admin\Block\Template
     */
    public static function singleton(): Template
    {
        if (self::$_instance === null || !self::$_instance instanceof Template) {
            self::$_instance = new Template();
        }
        return self::$_instance;
    }

    /**
     * @param string $string
     * @return $this
     */
    public function setTitle(string $string): static
    {
        $this->title = $string . ' - ' . Ddm::getTranslate('admin')->translate('后台管理中心');
        return $this;
    }

    /**
     * @param string $memuKey
     * @return $this
     */
    public function setActiveMemu(string $memuKey): static
    {
        $this->activeMemu = $memuKey;
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
    protected function _beforeFetch(): static
    {
        $this->addBlock($this->createBlock('admin', 'footer', 'footer.phtml'), 'footer');
        return $this;
    }

    protected function _construct(): static
    {
        parent::_construct();
        $this->addBlock($this->createBlock('admin', 'header', 'header.phtml')->addBlock($this->createBlock('admin', 'memus', 'memus.phtml'), 'memus')->addBlock($this->createBlock('core', 'notice')->setNotice(new Notice(true)), 'notice'), 'header');
        return $this;
    }
}

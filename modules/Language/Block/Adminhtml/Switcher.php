<?php

namespace Language\Block\Adminhtml;

use Core\Block\AbstractModel;
use Ddm;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Switcher extends AbstractModel
{

    protected mixed $_url = null;

    protected string $_queryVarName = 'language';

    /**
     * @return $this
     */
    public function init(): static
    {
        parent::init();
        $this->template = 'switch.phtml';
        return $this;
    }

    /**
     * @param int $languageId
     * @return string
     */
    public function getUrl(int $languageId): string
    {
        if ($this->_url === null) {
            return Ddm::getUrl('*/*/*', ['_current' => true, $this->getQueryVarName() => $languageId]);
        }
        return $this->_url . (strpos($this->_url, '?') ? '&' : '?') . $this->getQueryVarName() . "={$languageId}";
    }

    /**
     * @return string
     */
    public function getQueryVarName(): string
    {
        return $this->_queryVarName;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setUrl(string $url): static
    {
        $this->_url = $url;
        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setQueryVarName(string $name): static
    {
        $this->_queryVarName = $name;
        return $this;
    }
}

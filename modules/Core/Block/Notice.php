<?php

namespace Core\Block;

use Ddm\Notice as DdmNotice;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Notice extends AbstractModel
{

    protected mixed $_notice = null;

    /**
     * @param DdmNotice $notice
     * @return $this
     */
    public function setNotice(DdmNotice $notice): static
    {
        $this->_notice = $notice;
        return $this;
    }

    /**
     * @return DdmNotice
     */
    public function getNotice(): DdmNotice
    {
        return $this->_notice;
    }

    /**
     * @return string
     */
    protected function _toHtml(): string
    {
        return $this->getNotice()->toHtml();
    }
}

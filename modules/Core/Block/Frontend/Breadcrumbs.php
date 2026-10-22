<?php

namespace Core\Block\Frontend;

use Core\Block\AbstractModel;
use Ddm\StringHelper;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Breadcrumbs extends AbstractModel
{

    protected array $_crumbs = [];

    public function init(): static
    {
        $this->setTemplate('breadcrumbs.phtml');
        return parent::init();
    }

    /**
     * @param array $crumbInfo ['label' => ?[, 'link' => ?]];
     * @return $this
     */
    public function addCrumb(array $crumbInfo): static
    {
        if (!empty($crumbInfo['label'])) {
            $this->_crumbs[] = $crumbInfo;
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getCrumbs(): array
    {
        return $this->_crumbs;
    }

    /**
     * @param string $delimiter
     * @return string
     */
    public function getCrumbsHtml(string $delimiter = ' &gt; '): string
    {
        $html = '';
        foreach ($this->getCrumbs() as $crumbInfo) {
            $html == '' or $html .= $delimiter;
            $html .= empty($crumbInfo['link']) ? StringHelper::singleton()->escapeHtml($crumbInfo['label']) : '<a href="' . $crumbInfo['link'] . '">' . StringHelper::singleton()->escapeHtml($crumbInfo['label']) . '</a>';
        }
        return $html;
    }
}

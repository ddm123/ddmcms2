<?php

namespace Core\Block;

use Ddm;
use Ddm\Request;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Pagelink extends AbstractModel
{

    public string $pageVarName = 'p';

    public string $beforeHtml = '';

    public string $afterHtml = '';

    public bool $showinput = true;

    private int $_totalPage = 0;

    private mixed $_currentPage = null;

    private mixed $_currentUrl = null;

    private array $_pageVars = [];

    /**
     * @return $this
     */
    public function init(): static
    {
        parent::init();
        $this->_pageVars = ['_current' => true, $this->pageVarName => '{page}'];
        $this->template = 'pagelink.phtml';
        return $this;
    }

    /**
     * @return int
     */
    public function getCurrentPage(): int
    {
        if ($this->_currentPage === null) {
            $this->_currentPage = (int) Request::get($this->pageVarName);
            if (!$this->_currentPage || $this->_currentPage < 1) {
                $this->_currentPage = 1;
            }
            if ($this->_totalPage && $this->_currentPage > $this->_totalPage) {
                $this->_currentPage = $this->_totalPage;
            }
        }
        return $this->_currentPage;
    }

    /**
     * 计算起始序号
     * @return int
     */
    public function getStartNum(): int
    {
        $page = $this->getCurrentPage();
        if ($page <= 5 || $this->_totalPage <= 10) {
            $startNum = 1;
        } else {
            //$startNum = ($page%5==0) ? $page-4 : $page-($page%5+4);
            $startNum = $page - 5;
            if ($this->_totalPage - $startNum < 10) {
                $startNum = $this->_totalPage - 9;
            }
        }
        return $startNum;
    }

    /**
     * @param array|string $vars
     * @param mixed $value
     * @return $this
     */
    public function setPageVars(array|string $vars, mixed $value = null): static
    {
        if (is_array($vars)) {
            $this->_pageVars = array_merge($this->_pageVars, $vars);
        } else {
            $this->_pageVars[$vars] = $value;
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getPageVars(): array
    {
        return $this->_pageVars;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setCurrentUrl(string $url): static
    {
        $this->_currentUrl = $url;
        return $this;
    }

    public function getCurrentUrl(): mixed
    {
        return $this->_currentUrl === null ? $this->_currentUrl = Ddm::getLanguage()->getUrl('*/*/*', $this->getPageVars()) : $this->_currentUrl;
    }

    public function getUrlFromPage(mixed $page): mixed
    {
        return str_replace('{page}', $page, $this->getCurrentUrl());
    }

    /**
     * @param int $num
     * @return $this
     */
    public function setTotalPage(int $num): static
    {
        $this->_totalPage = abs((int) $num);
        $this->_currentPage = null;
        //重新获取当前页数
        return $this;
    }

    /**
     * @return int
     */
    public function getTotalPage(): int
    {
        return $this->_totalPage;
    }

    /**
     * @param string $varName
     * @return $this
     */
    public function setPageVarName(string $varName): static
    {
        $this->pageVarName = $varName;
        $this->_currentPage = null;
        //重新获取当前页数
        return $this;
    }

    /**
     * 获取分页所需的变量
     * 返回一个数组，[0 => 起始行数, 1 => 总页数, 2 => 当前第几页]
     * @param int $pageSize
     * @param int $totalRows
     * @return array
     */
    public function parseVars(int $pageSize, int $totalRows): array
    {
        $page = $this->getCurrentPage();
        $totalPage = ceil($totalRows / $pageSize);
        if ($page > $totalPage && $totalPage > 0) {
            $page = $totalPage;
        }
        $this->_currentPage = $page;
        $this->_totalPage = $totalPage;
        $startRow = ($page - 1) * $pageSize;
        return [$startRow, $totalPage, $page];
    }
}

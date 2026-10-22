<?php

namespace Core\Block\Frontend\List;

use Core\Block\AbstractModel as BlockAbstractModel;
use Core\Block\Pagelink;
use Core\Model\AbstractModel as ModelAbstractModel;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
abstract class AbstractModel extends BlockAbstractModel
{

    protected string $_pageVarName = 'p';

    protected int $_pageSize = 20;

    protected mixed $_orderBy = null;

    protected mixed $_totalRows = null;

    protected mixed $_list = null;

    protected mixed $_modelObject = null;

    protected mixed $_pageLinkBlock = null;

    protected int $_startRowNumber = 1;

    /**
     * @return ModelAbstractModel
     */
    abstract public function getModelObject(): ModelAbstractModel;

    /**
     * @param string $pageVarName
     * @return $this
     */
    public function setPageVarName(string $pageVarName): static
    {
        $this->_pageVarName = $pageVarName;
        return $this;
    }

    /**
     * @param int $pageSize
     * @return $this
     */
    public function setPageSize(int $pageSize): static
    {
        $this->_pageSize = (int) $pageSize;
        if ($this->_pageSize < 1) {
            $this->_pageSize = 20;
        }
        return $this;
    }

    /**
     * @param string $orderBy
     * @return $this
     */
    public function setOrderBy(string $orderBy): static
    {
        $this->_orderBy = $orderBy;
        return $this;
    }

    /**
     * @param ModelAbstractModel $model
     * @return $this
     */
    public function setModelObject(ModelAbstractModel $model): static
    {
        $this->_modelObject = $model;
        return $this;
    }

    /**
     * @return string
     */
    public function getPageVarName(): string
    {
        return $this->_pageVarName;
    }

    /**
     * @return int
     */
    public function getPageSize(): int
    {
        return $this->_pageSize;
    }

    /**
     * @return string
     */
    public function getOrderBy(): string
    {
        return $this->_orderBy;
    }

    /**
     * @return Pagelink
     */
    public function getPageLinkBlock(): Pagelink
    {
        return $this->_pageLinkBlock === null ? $this->_pageLinkBlock = $this->createBlock('Core', 'Pagelink')->setPageVarName($this->getPageVarName()) : $this->_pageLinkBlock;
    }

    /**
     * 获取总记录数
     * @return int
     */
    public function getCount(): int
    {
        if ($this->_totalRows === null) {
            $this->_beforeGetCount();
            $this->_totalRows = (int) $this->getModelObject()->getSelect()->count();
            $this->_afterGetCount();
        }
        return $this->_totalRows;
    }

    public function getList(): mixed
    {
        if ($this->_list === null) {
            if ($this->getCount() > 0) {
                $this->_beforeGetList();
                $vars = $this->getPageLinkBlock()->parseVars($this->getPageSize(), $this->getCount());
                $orderBy = $this->getOrderBy();
                $this->_startRowNumber = $vars[0] + 1;
                $builder = $this->getModelObject()->setLimit($this->getPageSize(), $vars[0])->getSelect();
                if ($orderBy && preg_match('/^(.+?)(?:\s+(asc|desc))?$/i', $orderBy, $matches)) {
                    $builder->orderBy($matches[1], isset($matches[2]) ? $matches[2] : 'asc');
                }
                $this->_list = $builder->get();
                $this->_afterGetList();
                $this->addBlock($this->getPageLinkBlock(), 'page');
            } else {
                $this->_list = [];
            }
        }
        return $this->_list;
    }

    /**
     * @return int
     */
    public function getStartRowNumber(): int
    {
        return $this->_startRowNumber;
    }

    /**
     * @return string
     */
    public function getPagesHtml(): string
    {
        return $this->getBlockHtml('page');
    }

    /**
     * @return $this
     */
    public function clear(): static
    {
        $this->_totalRows = null;
        $this->_list = null;
        return $this;
    }

    /**
     * @return $this
     */
    protected function _beforeGetCount(): static
    {
        return $this;
    }

    /**
     * @return $this
     */
    protected function _beforeGetList(): static
    {
        return $this;
    }

    /**
     * @return $this
     */
    protected function _afterGetCount(): static
    {
        return $this;
    }

    /**
     * @return $this
     */
    protected function _afterGetList(): static
    {
        return $this;
    }
}

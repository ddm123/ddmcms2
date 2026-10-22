<?php

namespace Admin\Block\List;

use Admin\Block\Grid;
use Core\Block\AbstractModel as BlockAbstractModel;
use Ddm;
use Ddm\Db\Builder;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
abstract class AbstractModel extends BlockAbstractModel
{

    /** @var Grid */
    protected mixed $_grid = null;

    protected string $_gridBlockName = 'grid_list';

    /**
     * @return $this
     */
    abstract protected function _initGrid(): static;

    /**
     * @return Grid
     */
    public function getGrid(): Grid
    {
        if ($this->_grid === null) {
            $this->_initGrid();
        }
        return $this->_grid;
    }

    /**
     * @return Grid
     */
    protected function _createGridBlock(): Grid
    {
        if ($this->_grid === null) {
            $this->_grid = $this->createBlock('admin', 'grid');
            $this->_grid->setListBlock($this);
        }
        return $this->_grid;
    }

    /**
     * @param Builder $select
     * @return $this
     */
    public function setSelect(Builder $select): static
    {
        $this->_grid->setSelect($select);
        return $this;
    }

    /**
     * @return Builder
     */
    public function getSelect(): Builder
    {
        return $this->_grid->getSelect();
    }

    /**
     * @param array $columnOption
     * @param string $fieldName
     * @param mixed $value
     * @return $this
     */
    public function applyFilter(array $columnOption, string $fieldName, mixed $value): static
    {
        list($operator, $value) = $this->_grid->getCondition($columnOption, $value);
        $this->_grid->getSelect()->where($fieldName, $operator, $value);
        return $this;
    }

    /**
     * @param string $fieldName
     * @param string $dir
     * @return $this
     */
    public function applySort(string $fieldName, string $dir): static
    {
        $this->_grid->getSelect()->orderBy($fieldName, $dir);
        return $this;
    }

    /**
     * @return $this
     */
    protected function _prepareColumns(): static
    {
        return $this;
    }

    /**
     * @return $this
     */
    protected function _prepareMassaction(): static
    {
        $this->getGrid()->addAction('delete', ['url' => Ddm::getLanguage()->getUrl('*/*/delete'), 'label' => Ddm::getTranslate('admin')->translate('删除'), 'confirm' => Ddm::getTranslate('admin')->translate('该操作不可撤消，确定要删除选择的记录？')]);
        return $this;
    }

    protected function _beforeToHtml(): static
    {
        parent::_beforeToHtml();
        $grid = $this->getGrid();
        $this->_prepareColumns();
        Ddm::dispatchEvent('gridlist_prepare_columns_after', ['gridlist' => $this, 'grid' => $grid]);
        $this->_prepareMassaction();
        Ddm::dispatchEvent('gridlist_prepare_massaction_after', ['gridlist' => $this, 'grid' => $grid]);
        $this->addBlock($grid, $this->_gridBlockName);
        return $this;
    }

    protected function _toHtml(): string
    {
        return $this->getBlockHtml($this->_gridBlockName);
    }
}

<?php

namespace Admin\Block;

use Core\Block\AbstractModel;
use Core\Model\DateTime;
use Ddm;
use Ddm\Db\Builder;
use Ddm\Db\Expression;
use Ddm\Request;
use Ddm\StringHelper;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Grid extends AbstractModel
{

    /** @var Builder */
    private mixed $_select = null;

    private int $_startRow = 0;

    protected array $_columns = [];

    protected mixed $_gridUrl = null;

    protected mixed $_gridSearchUrl = null;

    protected mixed $_resetGridSearchUrl = null;

    protected array $_searchColumns = [];

    protected mixed $_listData = null;

    protected bool $_filterData = false;

    protected array $_filterWhere = [];

    protected bool $_sortData = false;

    protected mixed $_disableRowCallback = null;

    protected array $_buttons = [];

    protected array $_actions = [];

    protected mixed $_listBlock = null;

    protected string $_js = '';

    protected bool $_isShowTotalRecords = true;

    public mixed $titleText = null;

    public mixed $primaryKey = null;
    //表主键的字段名

    public mixed $totalRows = null;

    public int $defaultLimit = 20;

    public int $defaultPage = 1;

    public bool $defaultSort = false;
    //是$this->_columns数据的键名,而不是表的字段名

    public string $defaultDir = 'desc';

    public array $defaultFilter = ['column_id' => false, 'keyword' => false];
    //设定默认一开始就搜索的列

    public string $idName = 'ids';

    public string $emptyText = '';

    public string $getFieldValueUrl = '';

    public string $saveFieldValueUrl = '';

    /**
     * @return $this
     */
    public function init(): static
    {
        parent::init();
        $this->titleText = Ddm::getTranslate('core')->translate('无标题');
        $this->emptyText = Ddm::getTranslate('core')->translate('没有任何记录');
        $this->template = 'grid.phtml';
        $this->addButton('add', ['label' => Ddm::getTranslate('admin')->translate('增加'), 'href' => Ddm::getLanguage()->getUrl('*/*/add'), 'icon' => 'icon-plus']);
        return $this;
    }

    /**
     * @param \Admin\Block\List\AbstractModel $block
     * @return $this
     */
    public function setListBlock(List\AbstractModel $block): static
    {
        $this->_listBlock = $block;
        return $this;
    }

    /**
     * @return \Admin\Block\List\AbstractModel
     */
    public function getListBlock(): List\AbstractModel
    {
        return $this->_listBlock;
    }

    /**
     * @param string $columnId
     * @param array $columnOption
     * @param int $index
     * @return $this
     */
    public function addColumn(string $columnId, array $columnOption, ?int $index = null): static
    {
        isset($columnOption['type']) or $columnOption['type'] = 'text';
        if (is_int($index)) {
            if ($index == 0) {
                $this->_columns = [$columnId => $columnOption] + $this->_columns;
            } else {
                $a = array_slice($this->_columns, 0, $index, true);
                $b = array_slice($this->_columns, $index, null, true);
                $this->_columns = array_merge($a, [$columnId => $columnOption], $b);
            }
        } else {
            $this->_columns[$columnId] = $columnOption;
        }
        if (!empty($columnOption['search'])) {
            $this->_searchColumns[$columnId] = $columnOption;
        }
        return $this;
    }

    /**
     * @param string $columnId
     * @return array|null
     */
    public function getColumn(?string $columnId = null): ?array
    {
        if ($columnId === null) {
            return $this->_columns;
        }
        return isset($this->_columns[$columnId]) ? $this->_columns[$columnId] : null;
    }

    /**
     * @param string $id
     * @param array $property
     * @return $this
     */
    public function addButton(string $id, array $property): static
    {
        $this->_buttons[$id] = $property;
        return $this;
    }

    /**
     * @param string $id
     * @param array $property
     * @return $this
     */
    public function updateButton(string $id, array $property): static
    {
        if (isset($this->_buttons[$id])) {
            foreach ($property as $key => $value) {
                if ($value === null) {
                    unset($this->_buttons[$id][$key]);
                } else {
                    $this->_buttons[$id][$key] = $value;
                }
            }
        }
        return $this;
    }

    /**
     * @param string $id
     * @return $this
     */
    public function removeButton(?string $id = null): static
    {
        if ($id === null) {
            $this->_buttons = [];
        } elseif (isset($this->_buttons[$id])) {
            unset($this->_buttons[$id]);
        }
        return $this;
    }

    /**
     * @param string $id
     * @return array
     */
    public function getButton(?string $id = null): array
    {
        if ($id === null) {
            return $this->_buttons;
        }
        return isset($this->_buttons[$id]) ? $this->_buttons[$id] : null;
    }

    /**
     * @param string $id
     * @return string
     */
    public function getButtonHtml(string $id): string
    {
        $html = '';
        if (isset($this->_buttons[$id])) {
            $html = '<a href="' . (isset($this->_buttons[$id]['href']) ? $this->_buttons[$id]['href'] : '#') . '" class="btn';
            isset($this->_buttons[$id]['class']) and $html .= ' ' . $this->_buttons[$id]['class'];
            $html .= '"';
            isset($this->_buttons[$id]['onclick']) and $html .= ' onclick="' . $this->_buttons[$id]['onclick'] . '"';
            isset($this->_buttons[$id]['style']) and $html .= ' style="' . $this->_buttons[$id]['style'] . '"';
            $html .= '>';
            isset($this->_buttons[$id]['icon']) and $html .= '<i class="icon ' . $this->_buttons[$id]['icon'] . '"></i> ';
            $html .= $this->_buttons[$id]['label'] . '</a>';
        }
        return $html;
    }

    /**
     * @param string $columnId
     * @return array|null
     */
    public function getSearchColumns(?string $columnId = null): ?array
    {
        if ($columnId === null) {
            return $this->_searchColumns;
        }
        return isset($this->_searchColumns[$columnId]) ? $this->_searchColumns[$columnId] : null;
    }

    /**
     *
     * @param callable $callback
     * @return $this
     */
    public function setDisableRowCallback(callable $callback): static
    {
        $this->_disableRowCallback = $callback;
        return $this;
    }

    /**
     * 你可以对选中的记录作出哪些操作
     * @param array $action 格式: ['url' => Post url, 'label' => Label[, 'confirm' => 'string']]
     * @return $this
     */
    public function addAction(mixed $key, array $action): static
    {
        $this->_actions[$key] = $action;
        return $this;
    }

    /**
     * @return array
     */
    public function getActions(mixed $key = null): array
    {
        return $key === null ? $this->primaryKey && $this->idName ? $this->_actions : [] : (isset($this->_actions[$key]) ? $this->_actions[$key] : null);
    }

    /**
     * @param string $key
     * @return $this
     */
    public function removeAction(string $key): static
    {
        if (isset($this->_actions[$key])) {
            unset($this->_actions[$key]);
        }
        return $this;
    }

    /**
     * @param string $javascrip
     * @return $this
     */
    public function addJs(string $javascrip): static
    {
        $this->_js .= "\r\n" . $javascrip;
        return $this;
    }

    /**
     * @return string
     */
    public function getJs(): string
    {
        return $this->_js;
    }

    /**
     * @param bool $flag
     * @return $this
     */
    public function setIsShowTotalRecords(bool $flag): static
    {
        $this->_isShowTotalRecords = (bool) $flag;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsShowTotalRecords(): bool
    {
        return $this->_isShowTotalRecords;
    }

    /**
     * @param array $row
     * @return boolean
     */
    public function isDisableRow(array $row): bool
    {
        if ($this->_disableRowCallback) {
            return call_user_func($this->_disableRowCallback, $row);
        }
        return false;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setGridUrl(string $url): static
    {
        $this->_gridUrl = $url;
        return $this;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setGridSearchUrl(string $url): static
    {
        $this->_gridSearchUrl = $url;
        return $this;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setResetGridSearchUrl(string $url): static
    {
        $this->_resetGridSearchUrl = $url;
        return $this;
    }

    /**
     * @param Builder $select
     * @return $this
     */
    public function setSelect(Builder $select): static
    {
        $this->_select = $select;
        return $this;
    }

    /**
     * @param string $columnId
     * @param string $keyword
     * @return $this
     */
    public function setDefaultFilter(string $columnId, string $keyword): static
    {
        $this->defaultFilter['column_id'] = $columnId;
        $this->defaultFilter['keyword'] = $keyword;
        return $this;
    }

    /**
     * @return Builder|null
     */
    public function getSelect(): ?Builder
    {
        return $this->_select;
    }

    /**
     * @return string
     */
    public function getGridUrl(): string
    {
        return $this->_gridUrl === null ? $this->_gridUrl = Ddm::getLanguage()->getUrl('*/*/*', ['_current' => true]) : $this->_gridUrl;
    }

    /**
     * @return string
     */
    public function getGridSearchUrl(): string
    {
        return $this->_gridSearchUrl === null ? $this->_gridSearchUrl = Ddm::getLanguage()->getUrl('*/*/*') : $this->_gridSearchUrl;
    }

    /**
     * @return string
     */
    public function getResetGridSearchUrl(): string
    {
        return $this->_resetGridSearchUrl === null ? $this->_resetGridSearchUrl = Ddm::getLanguage()->getUrl('*/*/*') : $this->_resetGridSearchUrl;
    }

    public function getTotalRows(): mixed
    {
        if ($this->totalRows === null) {
            if ($this->_select) {
                $this->_parseFilter()->_parseSort();
                $this->totalRows = $this->_select->count();
            }
        }
        return $this->totalRows;
    }

    /**
     * @return array
     */
    public function getListData(): array
    {
        if ($this->_listData === null) {
            if ($this->_select) {
                Ddm::dispatchEvent('grid_fetch_select_before', ['grid' => $this, 'select' => $this->_select]);
                if ($this->totalRows = (int) $this->getTotalRows()) {
                    $this->_parseFilter()->_parseSort();
                    if ($this->defaultLimit = (int) $this->defaultLimit) {
                        $pageLink = $this->createBlock('core', 'pagelink');
                        /** @var \Core\Block\Pagelink $pageLink */
                        $this->defaultPage = (int) $this->defaultPage;
                        if ($this->defaultPage > 1) {
                            $p = (int) Request::get($pageLink->pageVarName);
                            if (!$p) {
                                Request::singleton()->setParam($pageLink->pageVarName, $this->defaultPage);
                            }
                        }
                        list($this->_startRow, $totalPage) = $pageLink->parseVars($this->defaultLimit, $this->getTotalRows());
                        $this->_select->limit($this->defaultLimit, $this->_startRow);
                        $pageLink->setTotalPage($totalPage);
                        if ($this->getIsShowTotalRecords()) {
                            $pageLink->beforeHtml = Ddm::getTranslate('core')->___('共有%s条记录', ' <strong>' . $this->getTotalRows() . ' </strong>');
                        }
                        $this->addBlock($pageLink, 'pagelink');
                    }
                    $this->_listData = $this->_select->get(null, $this->primaryKey);
                } else {
                    $this->_listData = [];
                }
            } else {
                throw new \Exception('\'$select\' Property unassigned.');
            }
        }
        return $this->_listData;
    }

    /**
     * @param array $listData
     * @return $this
     */
    public function setListData(array $listData): static
    {
        $this->_listData = $listData;
        return $this;
    }

    /**
     * @return int
     */
    public function getStartNo(): int
    {
        return $this->_startRow + 1;
    }

    /**
     * @return array
     */
    public function getSortData(): array
    {
        $this->_sortData or $this->_parseSort();
        return $this->_sortData;
    }

    /**
     * @return array
     */
    public function getFilterData(): array
    {
        $this->_filterData or $this->_parseFilter();
        return $this->_filterData;
    }

    /**
     * @return array
     */
    public function getFilterWhere(): array
    {
        return $this->_parseFilter()->_filterWhere;
    }

    public function numberType(array $row, array $column): mixed
    {
        $value = empty($column['value_callback']) ? $row[$column['field_name']] + 0 : call_user_func($column['value_callback'], $row, $column);
        $html = '<td class="align-right"' . $this->_getEditHtml($row, $column) . '>' . $value . '</td>';
        return $html;
    }

    public function textType(array $row, array $column): mixed
    {
        $value = empty($column['value_callback']) ? StringHelper::singleton()->escapeHtml($row[$column['field_name']]) : call_user_func($column['value_callback'], $row, $column);
        $html = '<td' . $this->_getEditHtml($row, $column) . '>' . $value . '</td>';
        return $html;
    }

    public function optionsType(array $row, array $column): mixed
    {
        if (empty($column['value_callback'])) {
            $value = '&nbsp;';
            if (isset($column['options'])) {
                $value = isset($column['options'][$row[$column['field_name']]]) ? StringHelper::singleton()->escapeHtml($column['options'][$row[$column['field_name']]]) : '&nbsp;';
            }
        } else {
            $value = call_user_func($column['value_callback'], $row, $column);
        }
        $html = '<td' . $this->_getEditHtml($row, $column) . '>' . $value . '</td>';
        return $html;
    }

    public function actionType(array $row, array $column): mixed
    {
        $html = '<td>';
        foreach ($column['actions'] as $action) {
            if (preg_match_all('/\{(\w+)\}/', $action['url'], $matches, PREG_SET_ORDER)) {
                foreach ($matches as $matche) {
                    if (isset($row[$matche[1]])) {
                        $action['url'] = str_replace($matche[0], $row[$matche[1]], $action['url']);
                    }
                }
            }
            $html .= '<a href="' . $action['url'] . '"';
            if (!empty($action['target'])) {
                $html .= ' target="' . $action['target'] . '"';
            }
            if (!empty($action['onclick'])) {
                $html .= ' onclick="' . $action['onclick'] . '"';
            }
            $html .= '>' . $action['label'] . '</a> ';
        }
        $html .= '</td>';
        return $html;
    }

    public function datetimeType(array $row, array $column): mixed
    {
        $value = empty($column['value_callback']) ? Ddm::getHelper('core')->formatDateTime($row[$column['field_name']]) : call_user_func($column['value_callback'], $row, $column);
        $html = '<td' . $this->_getEditHtml($row, $column) . '>' . $value . '</td>';
        return $html;
    }

    public function dateType(array $row, array $column): mixed
    {
        $value = empty($column['value_callback']) ? Ddm::getHelper('core')->formatDate($row[$column['field_name']]) : call_user_func($column['value_callback'], $row, $column);
        $html = '<td' . $this->_getEditHtml($row, $column) . '>' . $value . '</td>';
        return $html;
    }

    public function currencyType(array $row, array $column): mixed
    {
        $value = empty($column['value_callback']) ? StringHelper::singleton()->currency($row[$column['field_name']]) : call_user_func($column['value_callback'], $row, $column);
        $html = '<td class="align-right"' . $this->_getEditHtml($row, $column) . '>' . $value . '</td>';
        return $html;
    }

    public function boolType(array $row, array $column): mixed
    {
        $value = empty($column['value_callback']) ? '<i class="icon icon-' . ($row[$column['field_name']] ? 'ok' : 'remove') . '"></i>' : call_user_func($column['value_callback'], $row, $column);
        $html = '<td class="align-center"' . $this->_getEditHtml($row, $column) . '>' . $value . '</td>';
        return $html;
    }

    /**
     * @param array $options
     * @return string
     */
    public function optionsToComboboxData(array $options): string
    {
        $html = '';
        foreach ($options as $key => $value) {
            $html == '' or $html .= ',';
            $html .= '["' . StringHelper::singleton()->escapeHtml($key) . '","' . StringHelper::singleton()->escapeHtml($value) . '"]';
        }
        return "[{$html}]";
    }

    /**
     * @param array $columnOption
     * @param mixed $value
     * @return array [$operator, $value]
     */
    public function getCondition(array $columnOption, mixed $value): array
    {
        if ($value instanceof Expression || is_array($value)) {
            return ['=', $value];
        }
        return $columnOption['type'] == 'currency' || $columnOption['type'] == 'number' || $columnOption['type'] == 'bool' ? ['=', $value + 0] : ['like', '%' . strtr($value, ['%' => '\%', '_' => '\_']) . '%'];
    }

    /**
     * @param array $row
     * @param array $column
     * @return string
     */
    protected function _getEditHtml(array $row, array $column): string
    {
        $html = '';
        if ($this->primaryKey && isset($row[$this->primaryKey]) && !empty($column['edit'])) {
            $html = ' modify="' . $column['field_name'] . '|' . $column['type'] . '|' . $row[$this->primaryKey] . '" title="' . Ddm::getTranslate('admin')->translate('双击可修改') . '"';
        }
        return $html;
    }

    /**
     * @return $this
     */
    protected function _parseFilter(): static
    {
        if (!$this->_filterData) {
            $columnId = Request::get('column', false, '') or $columnId = Request::post('column', false, '');
            ($keyword = Request::get('keyword', false, '')) === '' and $keyword = Request::post('keyword', false, '');
            if (($columnId == '' || $keyword === '') && $this->defaultFilter['column_id'] && $this->defaultFilter['keyword']) {
                $columnId = $this->defaultFilter['column_id'];
                $keyword = $this->defaultFilter['keyword'];
            }
            if ($columnId != '' && $keyword !== '' && $column = $this->getColumn($columnId)) {
                $this->_applyFilter($column, $keyword);
            }
            $this->_filterData = ['column' => $columnId, 'keyword' => $keyword];
        }
        return $this;
    }

    /**
     * @return \Admin\Block\Grid
     */
    protected function _parseSort(): Grid
    {
        if (!$this->_sortData) {
            $columnId = Request::get('col', false, '') or $columnId = Request::post('col', false, '');
            $dir = Request::get('dir', false, '') or $dir = Request::post('dir', false, '');
            $columnId == '' and $columnId = $this->defaultSort;
            $dir == '' and $dir = $this->defaultDir;
            if ($columnId && $column = $this->getColumn($columnId)) {
                $_dir = strtoupper($dir);
                $_dir == 'DESC' or $_dir = 'ASC';
                $fieldName = isset($column['column']) ? $column['column'] : $column['field_name'];
                if ($this->_listBlock) {
                    $this->_listBlock->applySort($fieldName, $_dir);
                } elseif (isset($column['sort_callback'])) {
                    call_user_func($column['sort_callback'], $this->_select, $fieldName, $_dir);
                } else {
                    $this->_select->order("{$fieldName} {$dir}");
                }
            }
            $this->_sortData = ['column' => $columnId, 'dir' => $dir];
        }
        return $this;
    }

    /**
     * @param array $column
     * @param string|array $keyword
     * @return $this
     */
    protected function _applyFilter(array &$column, string|array &$keyword): static
    {
        $columnName = isset($column['column']) ? $column['column'] : $column['field_name'];
        if (is_array($keyword)) {
            $kw = ['from' => isset($keyword['from']) ? $keyword['from'] : '', 'to' => isset($keyword['to']) ? $keyword['to'] : ''];
            if ($column['type'] == 'datetime' || $column['type'] == 'date') {
                $kw['from'] = empty($kw['from']) ? '' : $this->_datetimeToTimestamp($kw['from'], 0, 0, 0);
                $kw['to'] = empty($kw['to']) ? '' : $this->_datetimeToTimestamp($kw['to'], 23, 59, 59);
            } else {
                $kw['from'] === '' or $kw['from'] += 0;
                $kw['to'] === '' or $kw['to'] += 0;
            }
            if ($kw['from'] !== '' && $kw['to'] !== '') {
                $value = ['between' => $kw];
            } elseif ($kw['from'] !== '') {
                $value = ['>=' => $kw['from']];
            } elseif ($kw['to'] !== '') {
                $value = ['<=' => $kw['to']];
            } else {
                $value = 0;
            }
            if ($value) {
                $this->_filterWhere[$columnName] = $value;
            }
        } elseif (($keyword = trim($keyword)) !== '') {
            $this->_filterWhere[$columnName] = $keyword;
        }
        if ($this->_filterWhere) {
            if (isset($column['filter_callback'])) {
                call_user_func($column['filter_callback'], $this->_select, $columnName, $this->_filterWhere[$columnName]);
            } elseif ($this->_listBlock) {
                $this->_listBlock->applyFilter($column, $columnName, $this->_filterWhere[$columnName]);
            } else {
                list($operator, $value) = $this->getCondition($column, $this->_filterWhere[$columnName]);
                $this->_select->where($columnName, $operator, $value);
            }
        }
        return $this;
    }

    /**
     * @param string $datetime
     * @param int $hour
     * @param int $minute
     * @param int $second
     * @return int
     */
    protected function _datetimeToTimestamp(string $datetime, ?int $hour = null, ?int $minute = null, ?int $second = null): int
    {
        if ($datetime) {
            $timestamp = Ddm::getHelper('core')->getDateTime()->stringToTime($datetime);
            if ($hour !== null || $minute !== null || $second !== null) {
                $nowTime = explode(':', Ddm::getHelper('core')->getDateTime()->format('H:i:s'));
                $_dateTime = DateTime::getFromUnixtimestamp($timestamp, Ddm::getHelper('core')->getDateTime()->getTimezone());
                $_time = explode(':', $_dateTime->format('H:i:s'));
                $_dateTime->setTime($_time[0] == '00' ? $hour === null ? (int) $nowTime[0] : (int) $hour : (int) $_time[0], $_time[1] == '00' ? $minute === null ? (int) $nowTime[1] : (int) $minute : (int) $_time[1], $_time[2] == '00' ? $second === null ? (int) $nowTime[2] : (int) $second : (int) $_time[2]);
                $timestamp = $_dateTime->getTimestamp();
            }
            return $timestamp;
        }
        return $datetime;
    }
}

<?php

namespace Ddm\Db;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
/**
 * 表连接条件
 */
class JoinClause
{

    /** @var string */
    protected string $_type = 'inner';

    /** @var string */
    protected string $_table = '';

    /** @var string|null */
    protected mixed $_tableAlias = null;

    /** @var array<int, array> */
    protected array $_conditions = [];

    /**
     * @param string $table 已带表前缀的表名
     * @param string $type
     * @param string|null $alias 表别名
     */
    public function __construct(string $table, string $type = 'inner', ?string $alias = null)
    {
        $this->_table = $table;
        $this->_type = strtoupper($type);
        $this->_tableAlias = $alias;
        if (!in_array($this->_type, ['INNER', 'LEFT', 'RIGHT', 'OUTER', 'CROSS', 'NATURAL'], true)) {
            throw new \InvalidArgumentException('Invalid join type: ' . $type);
        }
    }

    /**
     * @param string|Closure $first
     * @param string|null $operator
     * @param string|null $second
     * @param string $boolean
     * @return \Ddm\Db\JoinClause
     */
    public function on(string|\Closure $first, ?string $operator = null, ?string $second = null, string $boolean = 'and'): JoinClause
    {
        if ($first instanceof \Closure) {
            return $this->_nested($first, $boolean);
        }
        if (func_num_args() === 2) {
            $second = $operator;
            $operator = '=';
        }
        return $this->_add('on', $first, $operator, $second, $boolean);
    }

    /**
     * @param string|Closure $first
     * @param string|null $operator
     * @param string|null $second
     * @return \Ddm\Db\JoinClause
     */
    public function orOn(string|\Closure $first, ?string $operator = null, ?string $second = null): JoinClause
    {
        if ($first instanceof \Closure) {
            return $this->_nested($first, 'or');
        }
        if (func_num_args() === 2) {
            $second = $operator;
            $operator = '=';
        }
        return $this->_add('on', $first, $operator, $second, 'or');
    }

    /**
     * @param string|Closure $column
     * @param string|null $operator
     * @param mixed $value
     * @param string $boolean
     * @return \Ddm\Db\JoinClause
     */
    public function where(string|\Closure $column, ?string $operator = null, mixed $value = null, string $boolean = 'and'): JoinClause
    {
        if ($column instanceof \Closure) {
            return $this->_nested($column, $boolean);
        }
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        return $this->_add('where', $column, $operator, $value, $boolean);
    }

    /**
     * @param string|Closure $column
     * @param string|null $operator
     * @param mixed $value
     * @return \Ddm\Db\JoinClause
     */
    public function orWhere(string|\Closure $column, ?string $operator = null, mixed $value = null): JoinClause
    {
        if ($column instanceof \Closure) {
            return $this->_nested($column, 'or');
        }
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        return $this->_add('where', $column, $operator, $value, 'or');
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->_type;
    }

    /**
     * @return string
     */
    public function getTable(): string
    {
        return $this->_table;
    }

    /**
     * @return string|null
     */
    public function getTableAlias(): ?string
    {
        return $this->_tableAlias;
    }

    /**
     * @return array<int, array>
     */
    public function getConditions(): array
    {
        return $this->_conditions;
    }

    /**
     * @param string $type
     * @param string|null $first
     * @param string|null $operator
     * @param string|null $second
     * @param string $boolean
     * @return $this
     */
    protected function _add(string $type, ?string $first, ?string $operator, ?string $second, string $boolean): static
    {
        $this->_conditions[] = ['type' => $type, 'first' => $first, 'operator' => $operator, 'second' => $second, 'boolean' => $boolean];
        return $this;
    }

    /**
     * @param Closure $callback
     * @param string $boolean
     * @return $this
     */
    protected function _nested(\Closure $callback, string $boolean): static
    {
        $join = new self($this->_table, $this->_type, $this->_tableAlias);
        $callback($join);
        $this->_conditions[] = ['type' => 'nested', 'join' => $join, 'boolean' => $boolean];
        return $this;
    }
}

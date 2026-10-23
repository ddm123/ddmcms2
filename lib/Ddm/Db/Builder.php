<?php

namespace Ddm\Db;

use Stringable;
use Ddm\Db;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Builder
{

    /** @var string 标识符引用符, 由数据库驱动决定, 默认MySQL反引号 */
    protected string $_identifierQuote = '`';

    /** @var string 表名(已带表前缀) */
    protected string $_table = '';

    /** @var string|null 表别名 */
    protected ?string $_tableAlias = null;

    /** @var array 查询的字段列表(空数组表示*) */
    protected array $_columns = [];

    /** @var \Ddm\Db\JoinClause[] 表连接列表 */
    protected array $_joins = [];

    /** @var array WHERE条件列表 */
    protected array $_wheres = [];

    /** @var array 排序列表 */
    protected array $_orders = [];

    /** @var array 分组列表 */
    protected array $_groups = [];

    /** @var array HAVING条件列表 */
    protected array $_havings = [];

    /** @var int|null 限制返回的行数 */
    protected ?int $_limit = null;

    /** @var int|null 偏移量 */
    protected ?int $_offset = null;

    /**
     * @param string $table 已带表前缀的表名
     * @param string|null $alias 表别名
     */
    public function __construct(string $table, ?string $alias = null)
    {
        $this->_table = $table;
        $this->_tableAlias = $alias;
    }

    /**
     * @return string
     */
    public function getTableName(): string
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
     * @param string|array $columns
     * @return $this
     */
    public function select(...$columns): static
    {
        $this->_columns = [];
        foreach ($columns as $column) {
            if (is_array($column)) {
                $this->_columns = array_merge($this->_columns, $column);
            } else {
                $this->_columns[] = $column;
            }
        }
        return $this;
    }

    /**
     * 追加查询字段(而不是覆盖)
     *
     * @param string|array $columns
     * @return $this
     */
    public function addSelect(...$columns): static
    {
        foreach ($columns as $column) {
            if (is_array($column)) {
                $this->_columns = array_merge($this->_columns, $column);
            } else {
                $this->_columns[] = $column;
            }
        }
        return $this;
    }

    /**
     * 查询多行
     *
     * @param string|array $columns
     * @param string|\Closure|null $indexBy
     * @return array
     */
    public function get(string|array $columns = [], string|\Closure|null $indexBy = null): array
    {
        if ($columns) {
            $this->select($columns);
        }
        $bindings = [];
        $sql = $this->_compileSelect($bindings);
        $stmt = $this->_read($sql, $bindings);
        if (!$indexBy) {
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }
        $result = [];
        if ($indexBy instanceof \Closure) {
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $result[$indexBy($row)] = $row;
            }
        } else {
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $result[$row[$indexBy]] = $row;
            }
        }
        return $result;
    }

    /**
     * 查询一行
     *
     * @param string|array $columns
     * @return array|null
     */
    public function first(string|array $columns = []): ?array
    {
        $rows = $this->limit(1, 0)->get($columns);
        return $rows ? $rows[0] : null;
    }

    /**
     * 通过主键查询一行
     *
     * @param mixed $id
     * @param string $column
     * @return array|null
     */
    public function find(mixed $id, string $column = 'id'): ?array
    {
        return $this->where($column, '=', $id)->first();
    }

    /**
     * 获取某一列的值列表
     *
     * @param string $column
     * @param string|null $key
     * @return array
     */
    public function pluck(string $column, ?string $key = null): array
    {
        $this->_columns = $key === null ? ['v' => $column] : ['v' => $column, 'k' => $key];
        $bindings = [];
        $sql = $this->_compileSelect($bindings);
        $stmt = $this->_read($sql, $bindings, \PDO::FETCH_NUM);
        $result = [];
        if ($key) {
            while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
                $result[$row[1]] = $row[0];
            }
        } else {
            while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
                $result[] = $row[0];
            }
        }
        return $result;
    }

    /**
     * 获取单个值
     *
     * @param string $column
     * @return string|null|false 如果没有查询到任何记录则返回false
     */
    public function value(string $column): string|null|false
    {
        $bindings = [];
        $this->_columns = ['v' => $column];
        $this->limit(1, 0);
        $sql = $this->_compileSelect($bindings);
        $stmt = $this->_read($sql, $bindings, \PDO::FETCH_NUM);
        $row = $stmt->fetch(\PDO::FETCH_NUM);
        return $row ? $row[0] : false;
    }

    /**
     * 获取当前SELECT语句
     *
     * @return string
     */
    public function toSql(array &$bindings = []): string
    {
        $bindings = [];
        return $this->_compileSelect($bindings);
    }

    /**
     * 统计行数
     *
     * @param string $columns
     * @return int
     */
    public function count(string $columns = '*'): int
    {
        $bindings = [];
        $column = $columns === '*' ? '*' : ($columns instanceof Expression ? (string) $columns : $this->wrap($columns));
        $sql = 'SELECT COUNT(' . $column . ') AS ' . $this->wrap('aggregate') . ' ' . $this->_compileFrom($bindings);
        if ($this->_groups) {
            $sql .= ' GROUP BY ' . $this->_compileGroups();
        }
        if ($this->_havings) {
            $sql .= ' HAVING ' . $this->_compileHavings($bindings);
        }
        $row = $this->_read($sql . ' LIMIT 1', $bindings)->fetch(\PDO::FETCH_ASSOC);
        return (int) $row['aggregate'];
    }

    /**
     * 是否存在记录
     *
     * @return bool
     */
    public function exists(): bool
    {
        $bindings = [];
        $row = $this->_read('SELECT 1 ' . $this->_compileFrom($bindings) . ' LIMIT 1', $bindings)->fetch(\PDO::FETCH_ASSOC);
        return $row ? true : false;
    }

    /**
     * @param string|Expression|\Closure $column
     * @param string|null $operator
     * @param mixed $value
     * @param string $boolean
     * @return \Ddm\Db\Builder
     */
    public function where(string|Expression|\Closure $column, ?string $operator = null, mixed $value = null, string $boolean = 'and'): Builder
    {
        if ($column instanceof \Closure) {
            return $this->_whereNested($column, $boolean);
        }
        $type = 'basic';
        if (func_num_args() <= 2) {
            $value = $operator;
            if (is_array($value)) {
                $type = 'in';
                $operator = null;
            } else {
                $operator = '=';
            }
        } elseif ($operator === null && is_array($value)) {
            $type = 'in';
        }
        return $this->_addWhere($type, $column, $operator, $value, $boolean);
    }

    /**
     * @param string|Expression|\Closure $column
     * @param string|null $operator
     * @param mixed $value
     * @return \Ddm\Db\Builder
     */
    public function orWhere(string|Expression|\Closure $column, ?string $operator = null, mixed $value = null): Builder
    {
        return $this->where($column, $operator, $value, 'or');
    }

    /**
     * @param string $column
     * @param array $values
     * @param string $boolean
     * @param bool $not
     * @return \Ddm\Db\Builder
     */
    public function whereIn(string $column, array $values, string $boolean = 'and', bool $not = false): Builder
    {
        return $this->_addWhere($not ? 'notin' : 'in', $column, null, (array) $values, $boolean);
    }

    /**
     * @param string $column
     * @param array $values
     * @return \Ddm\Db\Builder
     */
    public function orWhereIn(string $column, array $values): Builder
    {
        return $this->whereIn($column, $values, 'or');
    }

    /**
     * @param string $column
     * @param array $values
     * @param string $boolean
     * @return \Ddm\Db\Builder
     */
    public function whereNotIn(string $column, array $values, string $boolean = 'and'): Builder
    {
        return $this->whereIn($column, $values, $boolean, true);
    }

    /**
     * @param string $column
     * @param array $values
     * @return \Ddm\Db\Builder
     */
    public function orWhereNotIn(string $column, array $values): Builder
    {
        return $this->whereNotIn($column, $values, 'or');
    }

    /**
     * @param string $column
     * @param string $boolean
     * @param bool $not
     * @return \Ddm\Db\Builder
     */
    public function whereNull(string $column, string $boolean = 'and', bool $not = false): Builder
    {
        return $this->_addWhere($not ? 'notnull' : 'null', $column, null, null, $boolean);
    }

    /**
     * @param string $column
     * @return \Ddm\Db\Builder
     */
    public function orWhereNull(string $column): Builder
    {
        return $this->whereNull($column, 'or');
    }

    /**
     * @param string $column
     * @param string $boolean
     * @return \Ddm\Db\Builder
     */
    public function whereNotNull(string $column, string $boolean = 'and'): Builder
    {
        return $this->whereNull($column, $boolean, true);
    }

    /**
     * @param string $column
     * @return \Ddm\Db\Builder
     */
    public function orWhereNotNull(string $column): Builder
    {
        return $this->whereNotNull($column, 'or');
    }

    /**
     * @param string $column
     * @param string $from
     * @param string $to
     * @param string $boolean
     * @param bool $not
     * @return \Ddm\Db\Builder
     */
    public function whereBetween(string $column, string $from, string $to, string $boolean = 'and', bool $not = false): Builder
    {
        return $this->_addWhere($not ? 'notbetween' : 'between', $column, null, [$from, $to], $boolean);
    }

    /**
     * @param string|\Ddm\Db\Expression $column
     * @param string $direction
     * @return $this
     */
    public function orderBy(string|Expression $column, string $direction = 'asc'): static
    {
        if ($column === null) {
            $this->_orders[] = 'NULL';
        } else {
            $direction = strtoupper($direction);
            if ($direction !== 'ASC' && $direction !== 'DESC') {
                $direction = 'ASC';
            }
            $this->_orders[] = ($column instanceof Expression ? (string) $column : $this->wrap($column)) . ' ' . $direction;
        }
        return $this;
    }

    /**
     * @param string|array $groups
     * @return $this
     */
    public function groupBy(string|array $groups): static
    {
        $this->_groups = array_merge($this->_groups, is_array($groups) ? $groups : func_get_args());
        return $this;
    }

    /**
     * @param string $column
     * @param string|null $operator
     * @param mixed $value
     * @param string $boolean
     * @return \Ddm\Db\Builder
     */
    public function having(string $column, ?string $operator = null, mixed $value = null, string $boolean = 'and'): Builder
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        return $this->_addHaving($column, $operator, $value, $boolean);
    }

    /**
     * @param string $column
     * @param string|null $operator
     * @param mixed $value
     * @return \Ddm\Db\Builder
     */
    public function orHaving(string $column, ?string $operator = null, mixed $value = null): Builder
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        return $this->_addHaving($column, $operator, $value, 'or');
    }

    /**
     * @param int $value
     * @param int|null $offset
     * @return $this
     */
    public function limit(int $value, ?int $offset = null): static
    {
        $this->_limit = (int) $value;
        if ($offset !== null) {
            $this->_offset = (int) $offset;
        }
        return $this;
    }

    /**
     * @param int $value
     * @return $this
     */
    public function offset(int $value): static
    {
        $this->_offset = (int) $value;
        return $this;
    }

    /**
     * @param string $table
     * @param string|\Closure $first
     * @param string|null $operator
     * @param string|null $second
     * @param string $type
     * @return \Ddm\Db\Builder
     */
    public function join(string $table, string|\Closure $first, ?string $operator = null, ?string $second = null, string $type = 'inner'): Builder
    {
        return $this->_addJoin($table, $first, $operator, $second, $type);
    }

    /**
     * @param string $table
     * @param string|\Closure $first
     * @param string|null $operator
     * @param string|null $second
     * @return \Ddm\Db\Builder
     */
    public function leftJoin(string $table, string|\Closure $first, ?string $operator = null, ?string $second = null): Builder
    {
        return $this->_addJoin($table, $first, $operator, $second, 'left');
    }

    /**
     * @param string $table
     * @param string|\Closure $first
     * @param string|null $operator
     * @param string|null $second
     * @return \Ddm\Db\Builder
     */
    public function rightJoin(string $table, string|\Closure $first, ?string $operator = null, ?string $second = null): Builder
    {
        return $this->_addJoin($table, $first, $operator, $second, 'right');
    }

    /**
     * 新增一条记录
     *
     * @param array $values
     * @return int
     */
    public function insert(array $values): int
    {
        return Db::getWriteConn()->insert($this->_table, $values);
    }

    /**
     * 新增一条记录并返回自增ID
     *
     * @param array $values
     * @return int|null
     */
    public function insertGetId(array $values): ?int
    {
        $conn = Db::getWriteConn();
        $rows = $conn->insert($this->_table, $values);
        return $rows ? $conn->getLastInsertId() : null;
    }

    /**
     * 修改记录
     *
     * @param array $values
     * @return int 受影响的行数
     */
    public function update(array $values): int
    {
        $bindings = [];
        $sets = [];
        foreach ($values as $column => $value) {
            if ($value instanceof Expression) {
                $sets[] = $this->wrap($column) . ' = ' . $value;
            } else {
                $sets[] = $this->wrap($column) . ' = ?';
                $bindings[] = $value;
            }
        }
        if (!$sets) {
            return 0;
        }
        $wheres = $this->_compileWheres($bindings);
        $sql = 'UPDATE ' . $this->wrap($this->_table) . ' SET ' . implode(', ', $sets) . ($wheres ? ' WHERE ' . $wheres : '');
        return Db::getWriteConn()->query($sql, $bindings)->rowCount();
    }

    /**
     * 删除记录
     *
     * @param mixed $id 传ID则按主键id删除
     * @return int 受影响的行数
     */
    public function delete(mixed $id = null): int
    {
        if ($id !== null) {
            $this->where('id', '=', $id);
        }
        $bindings = [];
        $wheres = $this->_compileWheres($bindings);
        $sql = 'DELETE FROM ' . $this->wrap($this->_table) . ($wheres ? ' WHERE ' . $wheres : '');
        return Db::getWriteConn()->query($sql, $bindings)->rowCount();
    }

    /**
     * 字段自增
     *
     * @param string $column
     * @param int|float $amount
     * @param array $extra
     * @return int
     */
    public function increment(string $column, int|float $amount = 1, array $extra = []): int
    {
        $amount = (float) $amount;
        $extra[$column] = new Expression($this->wrap($column) . ($amount < 0 ? $amount : '+' . $amount));
        return $this->update($extra);
    }

    /**
     * 字段自减
     *
     * @param string $column
     * @param int $amount
     * @param array $extra
     * @return int
     */
    public function decrement(string $column, int $amount = 1, array $extra = []): int
    {
        return $this->increment($column, -$amount, $extra);
    }

    /**
     * @param string $sql
     * @param array $bindings
     * @param int $fetchMode
     * @return \PDOStatement|false
     */
    protected function _read(string $sql, array $bindings, int $fetchMode = \PDO::FETCH_ASSOC): \PDOStatement|false
    {
        return Db::getReadConn()->query($sql, $bindings, $fetchMode);
    }

    /**
     * @param string $type
     * @param string|\Ddm\Db\Expression $column
     * @param string|null $operator
     * @param mixed $value
     * @param string $boolean
     * @return $this
     */
    protected function _addWhere(string $type, string|Expression $column, ?string $operator, mixed $value, string $boolean): static
    {
        $boolean = strtolower($boolean) === 'or' ? 'or' : 'and';
        $this->_wheres[] = ['type' => $type, 'column' => $column, 'operator' => $operator ?: '=', 'value' => $value, 'boolean' => $boolean];
        return $this;
    }

    /**
     * @param \Closure $callback
     * @param string $boolean
     * @return $this
     */
    protected function _whereNested(\Closure $callback, string $boolean): static
    {
        $query = new self($this->_table, $this->_tableAlias);
        $callback($query);
        $boolean = strtolower($boolean) === 'or' ? 'or' : 'and';
        $this->_wheres[] = ['type' => 'nested', 'query' => $query, 'boolean' => $boolean];
        return $this;
    }

    /**
     * @param string|\Ddm\Db\Expression $column
     * @param string|null $operator
     * @param mixed $value
     * @param string $boolean
     * @return $this
     */
    protected function _addHaving(string|Expression $column, ?string $operator, mixed $value, string $boolean): static
    {
        $boolean = strtolower($boolean) === 'or' ? 'or' : 'and';
        $this->_havings[] = ['column' => $column, 'operator' => $operator ?: '=', 'value' => $value, 'boolean' => $boolean];
        return $this;
    }

    /**
     * @param string $table
     * @param string|\Closure $first
     * @param string|null $operator
     * @param string|null $second
     * @param string $type
     * @return $this
     */
    protected function _addJoin(string $table, string|\Closure $first, ?string $operator, ?string $second, string $type): static
    {
        list($table, $alias, $isPrefixIncluded) = self::splitTableAlias($table);
        $join = new JoinClause($isPrefixIncluded ? $table : Db::getTable($table), $type, $alias);
        if ($first instanceof \Closure) {
            $first($join);
        } else {
            $join->on($first, $operator, $second);
        }
        $this->_joins[] = $join;
        return $this;
    }

    /**
     * @param array $bindings
     * @return string
     */
    protected function _compileSelect(array &$bindings): string
    {
        $sql = 'SELECT ' . $this->_compileColumns() . ' ' . $this->_compileFrom($bindings);
        if ($this->_groups) {
            $sql .= ' GROUP BY ' . $this->_compileGroups();
        }
        if ($this->_havings) {
            $sql .= ' HAVING ' . $this->_compileHavings($bindings);
        }
        if ($this->_orders) {
            $sql .= ' ORDER BY ' . implode(', ', $this->_orders);
        }
        if ($this->_limit !== null) {
            $sql .= ' LIMIT ' . $this->_limit;
        }
        if ($this->_offset) {
            $sql .= ' OFFSET ' . $this->_offset;
        }
        return $sql;
    }

    /**
     * @param array $bindings
     * @return string
     */
    protected function _compileFrom(array &$bindings): string
    {
        $sql = 'FROM ' . $this->_compileTable($this->_table, $this->_tableAlias);
        foreach ($this->_joins as $join) {
            $sql .= ' ' . $this->_compileJoin($bindings, $join);
        }
        $wheres = $this->_compileWheres($bindings);
        if ($wheres) {
            $sql .= ' WHERE ' . $wheres;
        }
        return $sql;
    }

    /**
     * @param array $bindings
     * @param \Ddm\Db\JoinClause $join
     * @return string
     */
    protected function _compileJoin(array &$bindings, JoinClause $join): string
    {
        return $join->getType() . ' JOIN ' . $this->_compileTable($join->getTable(), $join->getTableAlias()) . ' ON ' . $this->_compileJoinConditions($bindings, $join->getConditions());
    }

    /**
     * @param array $bindings
     * @param array $conditions
     * @return string
     */
    protected function _compileJoinConditions(array &$bindings, array $conditions): string
    {
        $parts = [];
        foreach ($conditions as $i => $condition) {
            $boolean = $i === 0 ? '' : strtoupper($condition['boolean']) . ' ';
            switch ($condition['type']) {
                case 'on':
                    $parts[] = $boolean . $this->wrap($condition['first']) . ' ' . $condition['operator'] . ' ' . $this->wrap($condition['second']);
                    break;
                case 'where':
                    if ($condition['second'] instanceof Expression) {
                        $parts[] = $boolean . $this->wrap($condition['first']) . ' ' . $condition['operator'] . ' ' . $condition['second'];
                    } else {
                        $parts[] = $boolean . $this->wrap($condition['first']) . ' ' . $condition['operator'] . ' ?';
                        $bindings[] = $condition['second'];
                    }
                    break;
                case 'nested':
                    $sub = [];
                    $parts[] = $boolean . '(' . $this->_compileJoinConditions($sub, $condition['join']->getConditions()) . ')';
                    $bindings = array_merge($bindings, $sub);
                    break;
            }
        }
        return implode(' ', $parts);
    }

    /**
     * @return string
     */
    protected function _compileColumns(): string
    {
        if (empty($this->_columns)) {
            if (!$this->_joins) {
                return '*';
            }
            $this->_columns = [($this->_tableAlias ?: $this->_table) . '.*'];
        }
        $columns = [];
        foreach ($this->_columns as $alias => $column) {
            $columns[] = $this->_compileColumn($column, is_string($alias) ? $alias : null);
        }
        return implode(', ', $columns);
    }

    /**
     * 编译单个查询字段, 支持别名(数组键别名或 "expr AS alias")
     * @param string|\Ddm\Db\Expression $column
     * @param string|null $alias
     * @return string
     */
    protected function _compileColumn(string|Expression $column, ?string $alias = null): string
    {
        if ($column instanceof Expression) {
            $sql = (string) $column;
        } else {
            list($expr, $inlineAlias) = $this->_splitColumnAlias((string) $column);
            if ($alias === null) {
                $alias = $inlineAlias;
            }
            $sql = $this->wrap($expr);
        }
        return $alias === null ? $sql : $sql . ' AS ' . $this->wrap($alias);
    }

    /**
     * 从字段表达式中拆分出表达式与别名
     *
     * @param string $column
     * @return array [$expr, $alias]
     */
    protected function _splitColumnAlias(string $column): array
    {
        $column = trim($column);
        $parts = preg_split('/\s+AS\s+/i', $column, 2);
        if (count($parts) === 2) {
            return [trim($parts[0]), trim($parts[1])];
        }
        return [$column, null];
    }

    /**
     * 编译表名, 支持别名
     *
     * @param string $table
     * @param string|null $alias
     * @return string
     */
    protected function _compileTable(string $table, ?string $alias = null): string
    {
        $sql = $this->wrap($table);
        if ($alias) {
            $sql .= ' AS ' . $this->wrap($alias);
        }
        return $sql;
    }

    /**
     * @param array $bindings
     * @return string
     */
    protected function _compileWheres(array &$bindings): string
    {
        if (!$this->_wheres) {
            return '';
        }
        $parts = [];
        foreach ($this->_wheres as $where) {
            $sql = $this->_compileWhere($bindings, $where);
            if ($sql === '') {
                continue;
            }
            $parts[] = (empty($parts) ? '' : strtoupper($where['boolean']) . ' ') . $sql;
        }
        return implode(' ', $parts);
    }

    /**
     * @param array $bindings
     * @param array $where
     * @return string
     */
    protected function _compileWhere(array &$bindings, array $where): string
    {
        switch ($where['type']) {
            case 'nested':
                $sub = [];
                $sql = $where['query']->_compileWheres($sub);
                $bindings = array_merge($bindings, $sub);
                return $sql === '' ? '' : '(' . $sql . ')';
            case 'basic':
                return $this->_compileBasic($bindings, $where['column'], $where['operator'], $where['value']);
            case 'in':
            case 'notin':
                $values = $where['value'];
                if (empty($values)) {
                    return $this->wrap($where['column']) . ($where['type'] === 'in' ? ' IS NULL' : ' IS NOT NULL');
                }
                $placeholders = [];
                foreach ($values as $value) {
                    if ($value instanceof Expression) {
                        $placeholders[] = (string) $value;
                    } else {
                        $placeholders[] = '?';
                        $bindings[] = $value;
                    }
                }
                return $this->wrap($where['column']) . ($where['type'] === 'in' ? ' IN ' : ' NOT IN ') . '(' . implode(', ', $placeholders) . ')';
            case 'null':
            case 'notnull':
                return $this->wrap($where['column']) . ($where['type'] === 'null' ? ' IS NULL' : ' IS NOT NULL');
            case 'between':
            case 'notbetween':
                if ($where['value'][0] === $where['value'][1]) {
                    return $this->_compileBasic($bindings, $where['column'], '=', $where['value'][0]);
                }
                if ($where['value'][0] === null && $where['value'][1] === null) {
                    return $this->wrap($where['column']) . ($where['type'] === 'between' ? ' IS NULL' : ' IS NOT NULL');
                }
                if ($where['value'][0] === null) {
                    $bindings[] = $where['value'][1];
                    return $this->wrap($where['column']) . ($where['type'] === 'between' ? ' <= ?' : ' >= ?');
                }
                if ($where['value'][1] === null) {
                    $bindings[] = $where['value'][0];
                    return $this->wrap($where['column']) . ($where['type'] === 'between' ? ' >= ?' : ' <= ?');
                }
                $bindings[] = $where['value'][0];
                $bindings[] = $where['value'][1];
                return $this->wrap($where['column']) . ($where['type'] === 'between' ? ' BETWEEN ? AND ?' : ' NOT BETWEEN ? AND ?');
        }
        return '';
    }

    /**
     * @param array $bindings
     * @param string|\Ddm\Db\Expression $column
     * @param string $operator
     * @param mixed $value
     * @return string
     */
    protected function _compileBasic(array &$bindings, string|Expression $column, string $operator, mixed $value): string
    {
        $column = $this->wrap($column);
        if ($value instanceof Expression) {
            return $column . ' ' . $operator . ' ' . $value;
        }
        if ($value === null) {
            if ($operator === '=' || $operator === '==') {
                return $column . ' IS NULL';
            }
            if ($operator === '!=' || $operator === '<>') {
                return $column . ' IS NOT NULL';
            }
        }
        $bindings[] = $value;
        return $column . ' ' . $operator . ' ?';
    }

    /**
     * @return string
     */
    protected function _compileGroups(): string
    {
        $groups = [];
        foreach ($this->_groups as $group) {
            $groups[] = $group instanceof Expression ? (string) $group : $this->wrap($group);
        }
        return implode(', ', $groups);
    }

    /**
     * @param array $bindings
     * @return string
     */
    protected function _compileHavings(array &$bindings): string
    {
        $parts = [];
        foreach ($this->_havings as $i => $having) {
            $column = $having['column'] instanceof Expression ? (string) $having['column'] : $this->wrap($having['column']);
            if ($having['value'] instanceof Expression) {
                $parts[] = ($i === 0 ? '' : strtoupper($having['boolean']) . ' ') . $column . ' ' . $having['operator'] . ' ' . $having['value'];
            } else {
                $parts[] = ($i === 0 ? '' : strtoupper($having['boolean']) . ' ') . $column . ' ' . $having['operator'] . ' ?';
                $bindings[] = $having['value'];
            }
        }
        return implode(' ', $parts);
    }

    /**
     * 设置标识符引用符(不同数据库不同, MySQL为反引号, SQLite/PostgreSQL为双引号)
     *
     * @param string $quote
     * @return $this
     */
    public function setIdentifierQuote(string $quote): static
    {
        $this->_identifierQuote = (string) $quote;
        return $this;
    }

    /**
     * 从表名中拆分出表名与别名, 支持 [别名 => 表名] 或 "表名 as 别名"
     *
     * @param string|array $name
     * @param bool $isPrefixIncluded 表名是否已包含前缀
     * @return array [$table, $alias, $isPrefixIncluded]
     */
    public static function splitTableAlias(string|array $name, bool $isPrefixIncluded = false): array
    {
        $alias = null;
        if (is_array($name)) {
            $i = 0;
            foreach ($name as $k => $v) {
                if ($i === 0) {
                    $name = $v;
                    if (is_string($k)) {
                        $alias = $k;
                    }
                } else {
                    $isPrefixIncluded = (bool) $v;
                    break;
                }
                $i++;
            }
        } else {
            $name = trim((string) $name);
            $parts = preg_split('/\s+AS\s+/i', $name, 2);
            if (count($parts) === 2) {
                $name = trim($parts[0]);
                $alias = trim($parts[1]);
            }
        }
        return [$name, $alias, $isPrefixIncluded];
    }

    /**
     * 给字段名或表名加上引用符, 防止SQL注入
     *
     * @param string|\Ddm\Db\Expression $value
     * @return string
     */
    public function wrap(string|Expression $value): string
    {
        if ($value instanceof Expression) {
            return (string) $value;
        }
        $value = (string) $value;
        if ($value === '') {
            return $value;
        }
        $quote = $this->_identifierQuote;
        if (strpos($value, '.') !== false) {
            $parts = explode('.', $value);
            foreach ($parts as &$part) {
                $part = $this->_wrapPart($part, $quote);
            }
            return implode('.', $parts);
        }
        return $this->_wrapPart($value, $quote);
    }

    /**
     * @param string $part
     * @param string $quote
     * @return string
     */
    protected function _wrapPart(string $part, string $quote): string
    {
        if ($part === '*') {
            return '*';
        }
        if ($part !== '' && $part[0] === $quote && substr($part, -1) === $quote) {
            return $part;
        }
        return $quote . str_replace($quote, $quote . $quote, $part) . $quote;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->toSql();
    }
}

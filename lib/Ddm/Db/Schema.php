<?php

namespace Ddm\Db;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Schema
{

    /** @var \Ddm\Db\InterfaceDb */
    protected InterfaceDb $_connection;

    /** @var string */
    protected string $_tablePrefix = '';

    /** @var \Ddm\Db\Schema\Grammar */
    protected Schema\Grammar $_grammar;

    /**
     * @param \Ddm\Db\InterfaceDb $connection
     * @param string $tablePrefix
     * @param \Ddm\Db\Schema\Grammar $grammar
     * @return void
     */
    public function __construct(InterfaceDb $connection, string $tablePrefix, Schema\Grammar $grammar)
    {
        $this->_connection = $connection;
        $this->_tablePrefix = $tablePrefix;
        $this->_grammar = $grammar;
    }

    /**
     * 创建数据表
     *
     * @param string $table
     * @param callable(\Ddm\Db\Schema\Table $table): void $callback
     * @return \PDOStatement|false
     */
    public function create(string $table, callable $callback): \PDOStatement|false
    {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('The table definition must be callable.');
        }
        $table = $this->_getTableName($table);
        $definition = new Schema\Table($this->_grammar, $this->_tablePrefix);
        call_user_func($callback, $definition);
        $statements = $this->_grammar->compileCreate($table, $definition, false);
        return $this->_runStatements($statements);
    }

    /**
     * 创建数据表；表已存在时不执行操作
     *
     * @param string $table
     * @param callable(\Ddm\Db\Schema\Table $table): void $callback
     * @return \PDOStatement|false
     */
    public function createIfNotExists(string $table, callable $callback): \PDOStatement|false
    {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('The table definition must be callable.');
        }
        $table = $this->_getTableName($table);
        $definition = new Schema\Table($this->_grammar, $this->_tablePrefix);
        call_user_func($callback, $definition);
        return $this->_runStatements($this->_grammar->compileCreate($table, $definition, true));
    }

    /**
     * 删除数据表
     *
     * @param string $table
     * @return \PDOStatement|false
     */
    public function drop(string $table): \PDOStatement|false
    {
        return $this->_connection->query($this->_grammar->compileDrop($this->_getTableName($table), false));
    }

    /**
     * 删除数据表；表不存在时不报错
     *
     * @param string $table
     * @return \PDOStatement|false
     */
    public function dropIfExists(string $table): \PDOStatement|false
    {
        return $this->_connection->query($this->_grammar->compileDrop($this->_getTableName($table), true));
    }

    /**
     * 重命名数据表
     *
     * @param string $from
     * @param string $to
     * @return \PDOStatement|false
     */
    public function rename(string $from, string $to): \PDOStatement|false
    {
        return $this->_connection->query($this->_grammar->compileRename($this->_getTableName($from), $this->_getTableName($to)));
    }

    /**
     * 判断数据表是否存在
     *
     * @param string $table
     * @return bool
     */
    public function hasTable(string $table): bool
    {
        $statement = $this->_grammar->compileHasTable($this->_getTableName($table));
        $result = $this->_connection->query($statement['sql'], $statement['params']);
        return (bool) $result->fetch(\PDO::FETCH_NUM);
    }

    /**
     * 判断数据表是否包含指定字段
     *
     * @param string $table
     * @param string|array $columns
     * @return bool
     */
    public function hasColumn(string $table, string|array $columns): bool
    {
        $actual = $this->getColumnListing($table);
        foreach ((array) $columns as $column) {
            if (!in_array($column, $actual, true)) {
                return false;
            }
        }
        return true;
    }

    /**
     * 判断数据表是否包含所有指定字段
     *
     * @param string $table
     * @param string[] $columns
     * @return bool
     */
    public function hasColumns(string $table, array $columns): bool
    {
        return $this->hasColumn($table, $columns);
    }

    /**
     * 获取数据表字段列表
     *
     * @param string $table
     * @return string[]
     */
    public function getColumnListing(string $table): array
    {
        $statement = $this->_grammar->compileColumnListing($this->_getTableName($table));
        $result = $this->_connection->query($statement['sql'], $statement['params']);
        $columns = [];
        while ($row = $result->fetch(\PDO::FETCH_ASSOC)) {
            $columns[] = $row['name'];
        }
        return $columns;
    }

    /**
     * 获取当前数据库的数据表名称
     *
     * @return string[]
     */
    public function getTableListing(): array
    {
        $statement = $this->_grammar->compileTableListing();
        $result = $this->_connection->query($statement['sql'], $statement['params']);
        $tables = [];
        while ($row = $result->fetch(\PDO::FETCH_ASSOC)) {
            $tables[] = $row['name'];
        }
        return $tables;
    }

    /**
     * 删除当前数据库中的所有数据表
     *
     * @return void
     */
    public function dropAllTables(): void
    {
        $tables = $this->getTableListing();
        $connection = $this->_connection;
        $grammar = $this->_grammar;
        $this->withoutForeignKeyConstraints(function () use ($tables, $connection, $grammar) {
            foreach ($tables as $table) {
                $connection->query($grammar->compileDrop($table, true));
            }
        });
    }

    /**
     * 修改数据表
     *
     * @param string $table
     * @param callable(\Ddm\Db\Schema\Table $table): void $callback
     * @return \PDOStatement|false
     */
    public function table(string $table, callable $callback): \PDOStatement|false
    {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('The table definition must be callable.');
        }
        $table = $this->_getTableName($table);
        $definition = new Schema\Table($this->_grammar, $this->_tablePrefix);
        call_user_func($callback, $definition);
        return $this->_runStatements($this->_grammar->compileAlter($table, $definition));
    }

    /**
     * 禁用外键约束检查
     *
     * @return \PDOStatement|false
     */
    public function disableForeignKeyConstraints(): \PDOStatement|false
    {
        return $this->_connection->query($this->_grammar->compileForeignKeyChecks(false));
    }

    /**
     * 启用外键约束检查
     *
     * @return \PDOStatement|false
     */
    public function enableForeignKeyConstraints(): \PDOStatement|false
    {
        return $this->_connection->query($this->_grammar->compileForeignKeyChecks(true));
    }

    /**
     * 在回调执行期间禁用外键约束，并在结束后恢复
     *
     * @param callable $callback
     * @return mixed
     */
    public function withoutForeignKeyConstraints(callable $callback): mixed
    {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('The foreign key callback must be callable.');
        }
        $this->disableForeignKeyConstraints();
        try {
            $result = call_user_func($callback);
        } catch (\Exception $e) {
            $this->enableForeignKeyConstraints();
            throw $e;
        }
        $this->enableForeignKeyConstraints();
        return $result;
    }

    /**
     * @param string $table
     * @return string
     */
    protected function _getTableName(string $table): string
    {
        return $this->_tablePrefix . $table;
    }

    /**
     * @param string[] $statements
     * @return \PDOStatement|false
     */
    protected function _runStatements(array $statements): \PDOStatement|false
    {
        $result = false;
        foreach ($statements as $sql) {
            $result = $this->_connection->query($sql);
        }
        return $result;
    }
}

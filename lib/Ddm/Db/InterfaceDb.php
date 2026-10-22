<?php

namespace Ddm\Db;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

interface InterfaceDb
{

    /**
     * @param array $config
     */
    public function __construct(array $config);

    /**
     * 选择数据库
     *
     * @param string $databaseName
     * @return $this
     */
    public function setDatabase(string $databaseName): static;

    /**
     * 关闭数据连接
     *
     * @param resource $dblink
     * @return $this
     */
    public function close(mixed $dblink = false): static;

    /**
     * @param string $string
     * @param int $type
     * @return string|false
     */
    public function quote(string $string, int $type = \PDO::PARAM_STR): string|false;

    /**
     * @param string $sql
     * @param array $params
     * @param int $fetchMode
     * @return \PDOStatement|false
     */
    public function query(string $sql, array $params = [], int $fetchMode = \PDO::FETCH_ASSOC): \PDOStatement|false;

    /**
     * @param \PDOStatement $res
     * @return mixed
     */
    public function fetch(\PDOStatement $res): mixed;

    /**
     * @return string|false 最后一次插入的自增ID
     */
    public function getLastInsertId(): string|false;

    /**
     * 新增一条记录
     *
     * @param string $table
     * @param array $row
     * @param bool $useIgnore
     * @param array $OnDuplicateFields
     * @return int
     */
    public function insert(string $table, array $row, bool $useIgnore = false, array $OnDuplicateFields = []): int;

    /**
     * 新增多条记录
     *
     * @param string $table
     * @param array $rows
     * @param bool|array $useIgnore
     * @param array $OnDuplicateFields
     * @return int
     */
    public function insertMultiple(string $table, array $rows, bool|array $useIgnore = false, array $OnDuplicateFields = []): int;

    /**
     * 释放结果内存
     *
     * @param resource $result
     * @return $this
     */
    public function freeResult(mixed $result): static;

    /**
     * 返回当前连接的数据库版本
     *
     * @return string|null
     */
    public function getDbVersion(): ?string;

    /**
     * @return int 一共执行多少次SQL命令
     */
    public function getQueryCount(): int;

    /**
     * 开始一个事务
     *
     * @return bool
     */
    public function beginTransaction(): bool;

    /**
     * 提交事务
     *
     * @return bool
     */
    public function commit(): bool;

    /**
     * 回滚事务
     *
     * @return bool
     */
    public function rollBack(): bool;

    /**
     * 返回该数据库标识符(字段名/表名)的引用符
     *
     * @return string
     */
    public function getIdentifierQuote(): string;
}

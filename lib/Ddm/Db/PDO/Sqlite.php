<?php

namespace Ddm\Db\PDO;

use Ddm\Db\Expression;
use Ddm\Db\InterfaceDb;
use Ddm\Path;
use Ddm\Log;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Sqlite implements InterfaceDb
{

    /** @var \PDO */
    protected mixed $_pdo = null;

    /** @var int */
    protected int $_querynum = 0;

    /**
     * @param array $config
     * @return void
     */
    public function __construct(array $config)
    {
        if (!isset($config['dbname']) || $config['dbname'] === '') {
            $path = ':memory:';
        } else {
            $p = new Path(SITE_ROOT);
            $path = $p->join($config['dbname']);
            $dir = dirname($path);
            if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
                throw new \RuntimeException('目录 ' . $dir . ' 不存在');
            }
        }
        $this->_pdo = new \PDO('sqlite:' . $path, null, null, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::ATTR_EMULATE_PREPARES => false]);
        $this->_pdo->exec('PRAGMA foreign_keys = ON');
    }

    /**
     * @param string $databaseName 忽略(SQLite以文件为单位, 无多库概念)
     * @return $this
     */
    public function setDatabase(string $databaseName): static
    {
        return $this;
    }

    /**
     * @return $this
     */
    public function close(mixed $dblink = false): static
    {
        $this->_pdo = null;
        return $this;
    }

    /**
     * @param string $string
     * @param int $type
     * @return string|false
     */
    public function quote(string $string, int $type = \PDO::PARAM_STR): string|false
    {
        return $this->_pdo->quote($string, $type);
    }

    /**
     * @param string $sql
     * @param array $params
     * @param int $fetchMode
     * @return \PDOStatement|false
     */
    public function query(string $sql, array $params = [], int $fetchMode = \PDO::FETCH_ASSOC): \PDOStatement|false
    {
        try {
            $this->_querynum++;
            if ($params) {
                $sth = $this->_pdo->prepare($sql);
                $sth->setFetchMode($fetchMode);
                $sth->execute($params);
                return $sth;
            }
            return $this->_pdo->query($sql, $fetchMode);
        } catch (\Exception $e) {
            $this->_errorLog($e->getMessage() . PHP_EOL . 'SQL: ' . $sql . ($params ? PHP_EOL . 'Params: ' . var_export($params, true) : ''));
            throw $e;
        }
    }

    /**
     * @param \PDOStatement $res
     * @return mixed
     */
    public function fetch(\PDOStatement $res): mixed
    {
        return $res->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @return string|false 最后一次插入的自增ID
     */
    public function getLastInsertId(): string|false
    {
        return $this->_pdo->lastInsertId();
    }

    /**
     * 新增一条记录
     *
     * 注意: SQLite不支持MySQL的 ON DUPLICATE KEY UPDATE,
     * 当传入 $OnDuplicateFields 时使用 INSERT OR REPLACE 实现“存在则替换”,
     * 语义与MySQL略有差异(冲突时整行被删除后重新插入)。
     *
     * @param string $table
     * @param array $row
     * @param bool $useIgnore
     * @param array $OnDuplicateFields 如果出现重复则更新这些字段值
     * @return int
     */
    public function insert(string $table, array $row, bool $useIgnore = false, array $OnDuplicateFields = []): int
    {
        if (!$row) {
            return 0;
        }
        return $this->_doInsert($table, [$row], $useIgnore, $OnDuplicateFields);
    }

    /**
     * 新增多条记录
     *
     * @param string $table
     * @param array $rows
     * @param bool|array $useIgnore
     * @param array $OnDuplicateFields 如果出现重复则更新这些字段值
     * @return int
     */
    public function insertMultiple(string $table, array $rows, bool|array $useIgnore = false, array $OnDuplicateFields = []): int
    {
        if (!$rows) {
            return 0;
        }
        if (is_array($useIgnore) && !$OnDuplicateFields) {
            $OnDuplicateFields = $useIgnore;
            $useIgnore = false;
        }
        if (!isset($rows[0]) || !is_array($rows[0])) {
            return $this->insert($table, $rows, $useIgnore, $OnDuplicateFields);
        }
        return $this->_doInsert($table, $rows, $useIgnore, $OnDuplicateFields);
    }

    /**
     * 释放结果内存
     * @param mixed $result
     * @return $this
     */
    public function freeResult(mixed $result): static
    {
        if ($result instanceof \PDOStatement) {
            $result->closeCursor();
        }
        return $this;
    }

    /**
     * 返回当前连接的SQLite版本
     * @return string|null
     */
    public function getDbVersion(): ?string
    {
        $result = $this->query('SELECT sqlite_version() AS version');
        $row = $result->fetch(\PDO::FETCH_ASSOC);
        return $row ? $row['version'] : null;
    }

    /**
     * @return int 一共执行多少次SQL命令
     */
    public function getQueryCount(): int
    {
        return $this->_querynum;
    }

    /**
     * 开始一个事务
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->_pdo->beginTransaction();
    }

    /**
     * 提交事务
     * @return bool
     */
    public function commit(): bool
    {
        return $this->_pdo->commit();
    }

    /**
     * 回滚事务
     * @return bool
     */
    public function rollBack(): bool
    {
        return $this->_pdo->rollBack();
    }

    /**
     * SQLite标识符使用双引号
     * @return string
     */
    public function getIdentifierQuote(): string
    {
        return '"';
    }

    /**
     * 执行单条或多条INSERT
     * @param string $table
     * @param array $rows 二维数组
     * @param bool $useIgnore
     * @param array $OnDuplicateFields
     * @return int
     */
    protected function _doInsert(string $table, array $rows, bool $useIgnore, array $OnDuplicateFields): int
    {
        $fields = array_keys($rows[0]);
        $params = [];
        $valueRows = [];
        foreach ($rows as $row) {
            if (!$row) {
                continue;
            }
            $placeholders = [];
            foreach ($fields as $field) {
                $value = isset($row[$field]) ? $row[$field] : null;
                $placeholders[] = $this->_valuePlaceholder($value, $params);
            }
            $valueRows[] = '(' . implode(',', $placeholders) . ')';
        }
        if (!$valueRows) {
            return 0;
        }
        $quotedFields = [];
        foreach ($fields as $field) {
            $quotedFields[] = $this->_quote($field);
        }
        $sql = $this->_insertPrefix($useIgnore, $OnDuplicateFields) . ' INTO ' . $this->_quote($table) . ' (' . implode(',', $quotedFields) . ') VALUES ' . implode(',', $valueRows);
        $sth = $this->query($sql, $params);
        return $sth->rowCount();
    }

    /**
     * 把一个值转成SQL占位片段, 并把需要绑定的值追加到 $params
     * @param mixed $value
     * @param array $params
     * @return string
     */
    protected function _valuePlaceholder(mixed $value, array &$params): string
    {
        if ($value === null) {
            return 'NULL';
        }
        if ($value === true) {
            return '1';
        }
        if ($value === false) {
            return '0';
        }
        if ($value instanceof Expression) {
            return (string) $value;
        }
        $params[] = $value;
        return '?';
    }

    /**
     * 根据参数决定INSERT前缀(OR IGNORE / OR REPLACE)
     * @param bool $useIgnore
     * @param array $OnDuplicateFields
     * @return string
     */
    protected function _insertPrefix(bool $useIgnore, array $OnDuplicateFields): string
    {
        if ($OnDuplicateFields) {
            return 'INSERT OR REPLACE';
        }
        if ($useIgnore) {
            return 'INSERT OR IGNORE';
        }
        return 'INSERT';
    }

    /**
     * 给表名/字段名加上双引号
     * @param string $identifier
     * @return string
     */
    protected function _quote(string $identifier): string
    {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }

    /**
     * @param string $log
     * @return void
     */
    protected function _errorLog(string $log): void
    {
        Log::saveTo('sqlite/error.log', $log.PHP_EOL.'URL: '.Log::getCurrentUrl(true));
    }
}

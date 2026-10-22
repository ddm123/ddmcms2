<?php

namespace Ddm\Db\PDO;

use Ddm;
use Ddm\Db\Expression;
use Ddm\Db\InterfaceDb;

class Mysql implements InterfaceDb
{

    /** @var \PDO */
    protected mixed $_pdo = null;

    /** @var int */
    protected int $_querynum = 0;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $dsn = 'mysql:host=' . $config['host'];
        if (isset($config['port']) && $config['port'] !== '') {
            $dsn .= ';port=' . $config['port'];
        }
        if (isset($config['dbname']) && $config['dbname'] !== '') {
            $dsn .= ';dbname=' . $config['dbname'];
        }
        if (isset($config['character']) && $config['character'] !== '') {
            $dsn .= ';charset=' . $config['character'];
        }
        $this->_pdo = new \PDO($dsn, $config['username'], $config['password'], [\PDO::ATTR_PERSISTENT => !empty($config['use_pconnect']), \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::ATTR_EMULATE_PREPARES => true]);
    }

    /**
     * @param string $databaseName
     * @return $this
     */
    public function setDatabase(string $databaseName): static
    {
        $this->_pdo->exec('USE `' . $databaseName . '`');
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
     * @param string $table
     * @param array $row
     * @param bool|array $useIgnore
     * @param array $OnDuplicateFields 如果出现重复则更新这些字段值
     * @return int
     */
    public function insert(string $table, array $row, bool|array $useIgnore = false, array $OnDuplicateFields = []): int
    {
        if (!$row) {
            return 0;
        }
        if (is_array($useIgnore) && !$OnDuplicateFields) {
            $OnDuplicateFields = $useIgnore;
            $useIgnore = false;
        }
        $fields = array_keys($row);
        $fieldsCount = count($fields);
        $sql = 'INSERT';
        if ($useIgnore) {
            $sql .= ' IGNORE';
        }
        $sql .= ' INTO ' . $table . ' (`' . implode('`,`', $fields) . '`)';
        $sql .= ' VALUES (';
        $params = [];
        for ($i = 0; $i < $fieldsCount; $i++) {
            if ($row[$fields[$i]] === null) {
                $sql .= 'NULL';
            } elseif ($row[$fields[$i]] === true) {
                $sql .= '1';
            } elseif ($row[$fields[$i]] === false) {
                $sql .= '0';
            } elseif ($row[$fields[$i]] instanceof Expression) {
                $sql .= $row[$fields[$i]];
            } else {
                $sql .= '?';
                $params[] = $row[$fields[$i]];
            }
            if ($i < $fieldsCount - 1) {
                $sql .= ',';
            }
        }
        $sql .= ')';
        if ($OnDuplicateFields) {
            $sql .= ' ON DUPLICATE KEY UPDATE ';
            $updates = [];
            foreach ($OnDuplicateFields as $field => $value) {
                if (is_int($field)) {
                    $updates[] = "`{$value}`=VALUES(`{$value}`)";
                } elseif ($value === null) {
                    $updates[] = "`{$field}`=NULL";
                } elseif ($value === true) {
                    $updates[] = "`{$field}`=1";
                } elseif ($value === false) {
                    $updates[] = "`{$field}`=0";
                } elseif ($value instanceof Expression) {
                    $updates[] = "`{$field}`={$value}";
                } else {
                    $updates[] = "`{$field}`=?";
                    $params[] = $value;
                }
            }
            $sql .= implode(', ', $updates);
        }
        $sth = $this->query($sql, $params);
        return $sth->rowCount();
    }

    /**
     * 新增多条记录
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
        if (!isset($rows[0]) || !is_array($rows[0])) {
            return $this->insert($table, $rows, $useIgnore, $OnDuplicateFields);
        }
        if (is_array($useIgnore) && !$OnDuplicateFields) {
            $OnDuplicateFields = $useIgnore;
            $useIgnore = false;
        }
        $fields = array_keys($rows[0]);
        $sql = 'INSERT';
        if ($useIgnore) {
            $sql .= ' IGNORE';
        }
        $sql .= ' INTO ' . $table . ' (`' . implode('`,`', $fields) . '`) VALUES ';
        $params = [];
        $values = [];
        foreach ($rows as $row) {
            if (!$row) {
                continue;
            }
            $placeholders = [];
            foreach ($fields as $field) {
                $value = isset($row[$field]) ? $row[$field] : null;
                if ($value === null) {
                    $placeholders[] = 'NULL';
                } elseif ($value === true) {
                    $placeholders[] = '1';
                } elseif ($value === false) {
                    $placeholders[] = '0';
                } elseif ($value instanceof Expression) {
                    $placeholders[] = (string) $value;
                } else {
                    $placeholders[] = '?';
                    $params[] = $value;
                }
            }
            $values[] = '(' . implode(',', $placeholders) . ')';
        }
        if (!$values) {
            return 0;
        }
        $sql .= implode(',', $values);
        if ($OnDuplicateFields) {
            $sql .= ' ON DUPLICATE KEY UPDATE ';
            $dups = [];
            foreach ($OnDuplicateFields as $field => $value) {
                if (is_int($field)) {
                    $dups[] = "`{$value}`=VALUES(`{$value}`)";
                } elseif ($value === null) {
                    $dups[] = "`{$field}`=NULL";
                } elseif ($value === true) {
                    $dups[] = "`{$field}`=1";
                } elseif ($value === false) {
                    $dups[] = "`{$field}`=0";
                } elseif ($value instanceof Expression) {
                    $dups[] = "`{$field}`={$value}";
                } else {
                    $dups[] = "`{$field}`=?";
                    $params[] = $value;
                }
            }
            $sql .= implode(',', $dups);
        }
        $sth = $this->query($sql, $params);
        return $sth->rowCount();
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
     * 返回当前连接的MySQL的版本
     * @return string|null
     */
    public function getDbVersion(): ?string
    {
        $result = $this->query('SELECT VERSION() AS version');
        $row = $result->fetch(\PDO::FETCH_NUM);
        return $row ? $row[0] : null;
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
     * @return string
     */
    public function getIdentifierQuote(): string
    {
        return '`';
    }

    /**
     * @param string $log
     * @return void
     */
    protected function _errorLog(string $log): void
    {
        if (defined('SITE_ROOT')) {
            $f = SITE_ROOT . '/data/errors/mysql/mysql-errors.txt';
            $logstring = date('Y-m-d H:i:s') . "\r\n-----------------------------------";
            $logstring .= "\r\nURL: " . $_SERVER['REQUEST_URI'] . "\r\nIP: " . $_SERVER['REMOTE_ADDR'] . "\r\nUSER_AGENT: " . (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'None') . "\r\nMETHOD: " . $_SERVER['REQUEST_METHOD'];
            if (!empty($_POST)) {
                $logstring .= "\r\nPOST_DATA: " . print_r($_POST, true);
            }
            if (empty($_SERVER['QUERY_STRING']) && !empty($_GET)) {
                $logstring .= "\r\nGET_DATA: " . print_r($_GET, true);
            }
            $logstring .= "\r\n{$log}";
            if (is_file($f)) {
                if (filesize($f) > 4194304) {
                    rename($f, SITE_ROOT . '/data/errors/mysql/mysql-errors-' . date('YmdHis') . '.txt');
                } else {
                    Ddm::getHelper('core')->saveFile($f, "\r\n" . $logstring, FILE_APPEND);
                    return;
                }
            }
            Ddm::getHelper('core')->saveFile($f, $logstring);
        }
    }
}

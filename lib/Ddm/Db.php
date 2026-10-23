<?php

namespace Ddm;

use Ddm;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Db
{

    public const READ = 'read';

    public const WRITE = 'write';

    /** @var array<string, \Ddm\Db\InterfaceDb> */
    private static array $_connections = [];

    /** @var array|null */
    private static ?array $_databaseConfig = null;

    /** @var int */
    private static int $_beginTransaction = 0;

    /** @var string|null 'read' or 'write' */
    private static ?string $lockReadWiteType = null;

    /** @var string[] */
    private static array $lockReadWiteTypes = [];

    public function __clone(): void
    {
        trigger_error('Clone is not allowed.', E_USER_ERROR);
    }

    /**
     * 解除锁定读/写库
     *
     * @return void
     */
    public static function unLockReadWite(): void
    {
        self::$lockReadWiteType = self::$lockReadWiteTypes ? array_pop(self::$lockReadWiteTypes) : null;
    }

    /**
     * 锁定读库
     *
     * @return void
     */
    public static function lockReadConn(): void
    {
        self::lockConn(self::READ);
    }

    /**
     * 锁定写库
     *
     * @return void
     */
    public static function lockWriteConn(): void
    {
        self::lockConn(self::WRITE);
    }

    /**
     * 锁定读/写库
     *
     * @param string $type
     * @return void
     */
    public static function lockConn(string $type): void
    {
        self::$lockReadWiteTypes[] = self::$lockReadWiteType;
        self::$lockReadWiteType = $type ? $type : self::READ;
    }

    /**
     * @return \Ddm\Db\InterfaceDb
     */
    public static function getReadConn(): Db\InterfaceDb
    {
        return self::getConn(self::READ);
    }

    /**
     * @return \Ddm\Db\InterfaceDb
     */
    public static function getWriteConn(): Db\InterfaceDb
    {
        return self::getConn(self::WRITE);
    }

    /**
     * Connect to the database
     *
     * @param string $name 连接哪个数据库
     * @return \Ddm\Db\InterfaceDb
     */
    public static function getConn(string $name): Db\InterfaceDb
    {
        if (self::$lockReadWiteType) {
            $name = self::$lockReadWiteType;
        } elseif ($name === self::READ && self::isBeginTransaction()) {
            $name = self::WRITE;
        } elseif (!$name) {
            $name = self::READ;
        }
        if (!isset(self::$_connections[$name])) {
            self::_getDatabaseConfig();
            if (isset(self::$_databaseConfig[$name])) {
                self::$_connections[$name] = new self::$_databaseConfig[$name]['driver'](self::$_databaseConfig[$name]);
            } elseif (isset(self::$_databaseConfig['driver'])) {
                if (self::$_connections) {
                    return self::$_connections[$name] = current(self::$_connections);
                }
                self::$_connections[$name] = new self::$_databaseConfig['driver'](self::$_databaseConfig);
            } else {
                throw new \Exception("'{$name}' database driver is not defined.");
            }
        }
        return self::$_connections[$name];
    }

    /**
     * @return array<string, \Ddm\Db\InterfaceDb>
     */
    public static function getAllConnections(): array
    {
        return self::$_connections;
    }

    public static function clearCache(): void
    {
        foreach (self::$_connections as $connection) {
            $connection->close();
        }
        self::$_connections = [];
        self::$_databaseConfig = null;
    }

    /**
     * Get table name
     *
     * @param string $table
     * @return string
     */
    public static function getTable(string $table): string
    {
        self::_getDatabaseConfig();
        return self::$_databaseConfig['tablepre'] . $table;
    }

    /**
     * 获取数据库结构管理器
     *
     * @return \Ddm\Db\Schema
     */
    public static function schema(): Db\Schema
    {
        $connection = self::getWriteConn();
        $connectionClass = get_class($connection);
        $connectionPrefix = 'Ddm\Db\PDO\\';
        if (strpos($connectionClass, $connectionPrefix) !== 0) {
            throw new \InvalidArgumentException('Schema grammar is not defined for this database driver.');
        }
        $grammarClass = '\Ddm\Db\Schema\Grammar\\' . substr($connectionClass, strlen($connectionPrefix));
        if (!class_exists($grammarClass)) {
            throw new \InvalidArgumentException('Schema grammar "' . $grammarClass . '" is not defined.');
        }
        return new Db\Schema($connection, self::getTable(''), new $grammarClass());
    }

    /**
     * 获取一个查询构造器实例, 用于查询、修改、删除等操作
     *
     * @param string|array $name 表名, 也支持 [别名 => 表名] 或 "表名 as 别名"
     * @param bool $isPrefixIncluded 表名是否已包含前缀
     * @return \Ddm\Db\Builder
     */
    public static function table(string|array $name, bool $isPrefixIncluded = false): Db\Builder
    {
        list($table, $alias, $isPrefixIncluded) = Db\Builder::splitTableAlias($name, $isPrefixIncluded);
        $builder = new Db\Builder($isPrefixIncluded ? $table : self::getTable($table), $alias);
        return $builder->setIdentifierQuote(self::getReadConn()->getIdentifierQuote());
    }

    /**
     * @return int
     */
    public static function lastInsertId(): int
    {
        return self::getWriteConn()->getLastInsertId();
    }

    /**
     * @return bool
     */
    public static function beginTransaction(): bool
    {
        self::$_beginTransaction++;
        return self::$_beginTransaction === 1 ? self::getWriteConn()->beginTransaction() : false;
    }

    /**
     * @return bool
     */
    public static function commit(): bool
    {
        self::$_beginTransaction--;
        return self::$_beginTransaction === 0 ? self::getWriteConn()->commit() : false;
    }

    /**
     * @return bool
     */
    public static function rollBack(): bool
    {
        self::$_beginTransaction--;
        return self::$_beginTransaction === 0 ? self::getWriteConn()->rollBack() : false;
    }

    /**
     * @param callable $callback
     * @return mixed
     */
    public static function transaction(callable $callback): mixed
    {
        $result = null;
        self::beginTransaction();
        try {
            $result = $callback();
            self::commit();
        } catch (\Exception $e) {
            self::rollBack();
            throw $e;
        }
        return $result;
    }

    /**
     * @return bool
     */
    public static function isBeginTransaction(): bool
    {
        return self::$_beginTransaction > 0;
    }

    /**
     * @return array
     */
    protected static function _getDatabaseConfig(): array
    {
        return self::$_databaseConfig === null ? (self::$_databaseConfig = Ddm::getConfig()->getDbConfig()) : self::$_databaseConfig;
    }
}

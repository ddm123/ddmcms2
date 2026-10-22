<?php

namespace Ddm\Db\Schema\Grammar;

use Ddm\Db\Schema\Column;
use Ddm\Db\Schema\Grammar;
use Ddm\Db\Schema\Table;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Sqlite extends Grammar
{

    /**
     * @return string
     */
    public function getIdentifierQuote(): string
    {
        return '"';
    }

    /**
     * @param string $table
     * @return array
     */
    public function compileHasTable(string $table): array
    {
        return ['sql' => 'SELECT name FROM sqlite_master WHERE type = ? AND name = ? LIMIT 1', 'params' => ['table', $table]];
    }

    /**
     * @param string $table
     * @return array
     */
    public function compileColumnListing(string $table): array
    {
        // Columns: name, type, notnull, dflt_value, pk
        return ['sql' => 'PRAGMA table_info(' . $this->wrap($table) . ')', 'params' => []];
    }

    /**
     * @return array
     */
    public function compileTableListing(): array
    {
        return ['sql' => "SELECT name FROM sqlite_master WHERE type = ? AND name NOT LIKE 'sqlite_%' ORDER BY name", 'params' => ['table']];
    }

    /**
     * @param bool $enable
     * @return string
     */
    public function compileForeignKeyChecks(bool $enable): string
    {
        return 'PRAGMA foreign_keys=' . (int) (bool) $enable;
    }

    /**
     * SQLite has no equivalent of MySQL's engine, charset, collation, or table comment options.
     * Unsupported table options are intentionally omitted for this driver.
     *
     * @param array $options
     * @return string
     */
    protected function _compileTableOptions(array $options): string
    {
        return '';
    }

    /**
     * @param string $table
     * @param Table $definition
     * @return string[]
     */
    public function compileAlter(string $table, Table $definition): array
    {
        $statements = [];
        foreach ($definition->getColumns() as $column) {
            if ($column->isChange()) {
                throw new \LogicException('SQLite does not support modifying a column directly.');
            }
            $statements[] = 'ALTER TABLE ' . $this->wrap($table) . ' ADD COLUMN ' . $this->compileColumn($column);
        }
        foreach ($definition->compileIndexes($table) as $statement) {
            $statements[] = $statement;
        }
        if ($definition->getForeignKeys()) {
            throw new \LogicException('SQLite does not support adding foreign keys to an existing table without rebuilding it.');
        }
        foreach ($definition->getCommands() as $command) {
            switch ($command['name']) {
                case 'dropColumn':
                    foreach ($command['columns'] as $column) {
                        $statements[] = 'ALTER TABLE ' . $this->wrap($table) . ' DROP COLUMN ' . $this->wrap($column);
                    }
                    break;
                case 'renameColumn':
                    $statements[] = 'ALTER TABLE ' . $this->wrap($table) . ' RENAME COLUMN ' . $this->wrap($command['from']) . ' TO ' . $this->wrap($command['to']);
                    break;
                case 'renameIndex':
                    throw new \LogicException('SQLite does not support renaming indexes directly.');
                case 'dropUnique':
                case 'dropIndex':
                    $index = $command['index'];
                    if ($index === null) {
                        $suffix = $command['indexType'] === 'unique' ? '_unique' : '_index';
                        $index = $table . '_' . implode('_', $command['columns']) . $suffix;
                    }
                    $statements[] = 'DROP INDEX ' . $this->wrap($index);
                    break;
                case 'dropPrimary':
                case 'dropForeign':
                    throw new \LogicException('SQLite cannot drop primary or foreign key constraints without rebuilding the table.');
            }
        }
        return $statements;
    }

    /**
     * @param string $type
     * @param array $options
     * @return string
     */
    public function getColumnType(string $type, array $options): string
    {
        $length = isset($options['length']) ? (int) $options['length'] : 0;
        switch ($type) {
            case 'increments':
            case 'bigIncrements':
            case 'mediumIncrements':
            case 'smallIncrements':
            case 'tinyIncrements':
            case 'integer':
            case 'bigInteger':
            case 'mediumInteger':
            case 'smallInteger':
            case 'tinyInteger':
            case 'boolean':
                return 'INTEGER';
            case 'string':
                return 'VARCHAR(' . ($length ?: 255) . ')';
            case 'char':
            case 'uuid':
            case 'ulid':
                return $type === 'uuid' ? 'CHAR(36)' : ($type === 'ulid' ? 'CHAR(26)' : 'CHAR(' . ($length ?: 255) . ')');
            case 'text':
            case 'tinyText':
            case 'mediumText':
            case 'longText':
                return 'TEXT';
            case 'decimal':
            case 'float':
            case 'double':
                return 'NUMERIC';
            case 'json':
            case 'jsonb':
            case 'enum':
                return 'TEXT';
            case 'binary':
                return 'BLOB';
            case 'year':
                return 'INTEGER';
            case 'date':
                return 'DATE';
            case 'time':
                return 'TIME';
            case 'dateTime':
            case 'timestamp':
            case 'dateTimeTz':
            case 'timestampTz':
                return 'DATETIME';
            case 'timeTz':
                return 'TIME';
        }
        throw new \InvalidArgumentException('Unsupported SQLite schema column type: ' . $type);
    }

    /**
     * @param Column $column
     * @return string
     */
    public function compileColumn(Column $column): string
    {
        $options = $column->getOptions();
        $sql = parent::compileColumn($column);
        if ($column->getType() === 'enum') {
            $allowed = $options['allowed'];
            if (!$allowed) {
                throw new \InvalidArgumentException('An enum column requires at least one allowed value.');
            }
            $values = [];
            foreach ($allowed as $value) {
                $values[] = "'" . str_replace("'", "''", (string) $value) . "'";
            }
            $sql .= ' CHECK (' . $this->wrap($column->getName()) . ' IN (' . implode(', ', $values) . '))';
        }
        return $sql;
    }

    /**
     * SQLite's AUTOINCREMENT syntax requires a column whose declared type is INTEGER.
     * Treat auto-increment as the dominant column modifier regardless of the requested
     * integer width or alias used by the schema definition.
     *
     * @param Column $column
     * @return string
     */
    protected function getColumnDefinitionType(Column $column): string
    {
        if ($column->isAutoIncrement()) {
            return 'INTEGER';
        }
        return parent::getColumnDefinitionType($column);
    }

    /**
     * @param string $table
     * @param string $name
     * @param array $columns
     * @param bool $unique
     * @param string $type
     * @param bool $ifNotExists
     * @return string
     */
    public function compileIndex(string $table, string $name, array $columns, bool $unique, string $type = 'index', bool $ifNotExists = false): string
    {
        // Preserve index and uniqueness behavior using SQLite's ordinary index syntax.
        return parent::compileIndex($table, $name, $columns, $unique, 'index', $ifNotExists);
    }

    /**
     * @param Column $column
     * @return string
     */
    protected function _compileAutoIncrement(Column $column): string
    {
        return ' PRIMARY KEY AUTOINCREMENT';
    }

    /**
     * @param array $columns
     * @param bool $hasAutoIncrementPrimary
     * @return string|null
     */
    public function compilePrimary(array $columns, bool $hasAutoIncrementPrimary): ?string
    {
        return $hasAutoIncrementPrimary ? null : parent::compilePrimary($columns, false);
    }
}

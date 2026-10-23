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
class Mysql extends Grammar
{

    /**
     * @return string
     */
    public function getIdentifierQuote(): string
    {
        return '`';
    }

    /**
     * @param string $table
     * @return array
     */
    public function compileHasTable(string $table): array
    {
        return ['sql' => 'SELECT 1 AS name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1', 'params' => [$table]];
    }

    /**
     * @param string $table
     * @return array
     */
    public function compileColumnListing(string $table): array
    {
        return ['sql' => 'SELECT COLUMN_NAME AS `name`,COLUMN_TYPE AS `type`,COLUMN_DEFAULT AS `default`,IS_NULLABLE AS `nullable`,COLUMN_KEY AS `key` FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? ORDER BY ordinal_position ASC', 'params' => [$table]];
    }

    /**
     * @return array
     */
    public function compileTableListing(): array
    {
        return ['sql' => 'SELECT table_name AS name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = ? ORDER BY table_name', 'params' => ['BASE TABLE']];
    }

    /**
     * @param bool $enable
     * @return string
     */
    public function compileForeignKeyChecks(bool $enable): string
    {
        return 'SET FOREIGN_KEY_CHECKS=' . (int) (bool) $enable;
    }

    /**
     * @param string $table
     * @param Table $definition
     * @return string[]
     */
    protected function compileCreateTableIndexes(string $table, Table $definition): array
    {
        $indexes = [];
        foreach ($definition->getIndexDefinitions($table) as $index) {
            $type = $index['unique'] ? 'UNIQUE ' : ($index['type'] === 'fulltext' ? 'FULLTEXT ' : ($index['type'] === 'spatial' ? 'SPATIAL ' : ''));
            $indexes[] = $type . 'KEY ' . $this->wrap($index['name']) . ' (' . implode(', ', $this->_wrapIdentifiers($index['columns'])) . ')';
        }
        return $indexes;
    }

    /**
     * @param string $table
     * @param Table $definition
     * @param bool $ifNotExists
     * @return string[]
     */
    protected function compileCreateIndexes(string $table, Table $definition, bool $ifNotExists): array
    {
        return [];
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
                $statements[] = 'ALTER TABLE ' . $this->wrap($table) . ' MODIFY COLUMN ' . $this->compileColumn($column);
            } else {
                $statements[] = 'ALTER TABLE ' . $this->wrap($table) . ' ADD COLUMN ' . $this->compileColumn($column) . $this->_compileColumnPosition($column);
            }
        }
        foreach ($definition->compileIndexes($table) as $statement) {
            $statements[] = $statement;
        }
        foreach ($definition->getForeignKeys() as $foreign) {
            $statements[] = 'ALTER TABLE ' . $this->wrap($table) . ' ADD ' . $this->compileForeignKey($table, $foreign, $definition->getTablePrefix());
        }
        foreach ($definition->getCommands() as $command) {
            $name = isset($command['index']) ? $command['index'] : null;
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
                    $statements[] = 'ALTER TABLE ' . $this->wrap($table) . ' RENAME INDEX ' . $this->wrap($command['from']) . ' TO ' . $this->wrap($command['to']);
                    break;
                case 'dropPrimary':
                    $statements[] = 'ALTER TABLE ' . $this->wrap($table) . ' DROP PRIMARY KEY';
                    break;
                case 'dropUnique':
                case 'dropIndex':
                    $index = $name;
                    if ($index === null) {
                        $suffix = $command['indexType'] === 'fulltext' ? '_fulltext' : ($command['indexType'] === 'spatial' ? '_spatialindex' : ($command['indexType'] === 'unique' ? '_unique' : '_index'));
                        $index = $table . '_' . implode('_', $command['columns']) . $suffix;
                    }
                    $statements[] = 'ALTER TABLE ' . $this->wrap($table) . ' DROP INDEX ' . $this->wrap($index);
                    break;
                case 'dropForeign':
                    $index = $name;
                    if ($index === null) {
                        $index = $table . '_' . implode('_', $command['columns']) . '_foreign';
                    }
                    $statements[] = 'ALTER TABLE ' . $this->wrap($table) . ' DROP FOREIGN KEY ' . $this->wrap($index);
                    break;
            }
        }
        return $statements;
    }

    /**
     * @param Column $column
     * @return string
     */
    protected function _compileColumnPosition(Column $column): string
    {
        $options = $column->getOptions();
        if (!empty($options['first'])) {
            return ' FIRST';
        }
        if (isset($options['after'])) {
            return ' AFTER ' . $this->wrap($options['after']);
        }
        return '';
    }

    /**
     * @param Column $column
     * @return string
     */
    public function compileColumn(Column $column): string
    {
        $sql = parent::compileColumn($column);
        $options = $column->getOptions();
        if (!empty($options['useCurrentOnUpdate'])) {
            $sql .= ' ON UPDATE CURRENT_TIMESTAMP';
        }
        if (isset($options['comment'])) {
            $sql .= " COMMENT '" . $this->_escapeString($options['comment']) . "'";
        }
        return $sql;
    }

    /**
     * @param string $type
     * @param array $options
     * @return string
     */
    public function getColumnType(string $type, array $options): string
    {
        $length = isset($options['length']) ? (int) $options['length'] : 0;
        $precision = isset($options['precision']) ? (int) $options['precision'] : 0;
        $scale = isset($options['scale']) ? (int) $options['scale'] : 0;
        $allowed = isset($options['allowed']) ? $options['allowed'] : [];
        switch ($type) {
            case 'increments':
                return 'INT';
            case 'mediumIncrements':
            case 'mediumInteger':
                return 'MEDIUMINT';
            case 'bigIncrements':
            case 'bigInteger':
                return 'BIGINT';
            case 'smallIncrements':
                return 'SMALLINT';
            case 'tinyIncrements':
                return 'TINYINT';
            case 'integer':
                return 'INT';
            case 'smallInteger':
                return 'SMALLINT';
            case 'tinyInteger':
                return 'TINYINT';
            case 'string':
                return 'VARCHAR(' . ($length ?: 255) . ')';
            case 'char':
                return 'CHAR(' . ($length ?: 255) . ')';
            case 'text':
                return 'TEXT';
            case 'tinyText':
                return 'TINYTEXT';
            case 'mediumText':
                return 'MEDIUMTEXT';
            case 'longText':
                return 'LONGTEXT';
            case 'boolean':
                return 'TINYINT(1)';
            case 'date':
                return 'DATE';
            case 'time':
                return 'TIME';
            case 'dateTime':
            case 'timestamp':
                return 'DATETIME';
            case 'dateTimeTz':
            case 'timestampTz':
                return 'DATETIME';
            case 'timeTz':
                return 'TIME';
            case 'float':
                return 'FLOAT(' . ($precision ?: 8) . ',' . ($scale ?: 2) . ')';
            case 'double':
                return 'DOUBLE(' . ($precision ?: 15) . ',' . ($scale ?: 2) . ')';
            case 'decimal':
                return 'DECIMAL(' . ($precision ?: 8) . ',' . ($scale ?: 2) . ')';
            case 'binary':
                return 'BLOB';
            case 'uuid':
                return 'CHAR(36)';
            case 'ulid':
                return 'CHAR(26)';
            case 'json':
            case 'jsonb':
                return 'JSON';
            case 'year':
                return 'YEAR';
            case 'enum':
                if (!$allowed) {
                    throw new \InvalidArgumentException('An enum column requires at least one allowed value.');
                }
                $values = [];
                foreach ($allowed as $value) {
                    $values[] = "'" . str_replace("'", "''", (string) $value) . "'";
                }
                return 'ENUM(' . implode(', ', $values) . ')';
        }
        throw new \InvalidArgumentException('Unsupported MySQL schema column type: ' . $type);
    }

    /**
     * @param Column $column
     * @return string
     */
    protected function _compileUnsigned(Column $column): string
    {
        if (!in_array($column->getType(), ['increments', 'bigIncrements', 'mediumIncrements', 'smallIncrements', 'tinyIncrements', 'integer', 'bigInteger', 'mediumInteger', 'smallInteger', 'tinyInteger', 'float', 'double', 'decimal', 'boolean'], true)) {
            throw new \InvalidArgumentException('Unsigned is only supported for numeric MySQL columns.');
        }
        return ' UNSIGNED';
    }

    /**
     * @param Column $column
     * @return string
     */
    protected function _compileAutoIncrement(Column $column): string
    {
        if (!in_array($column->getType(), ['increments', 'bigIncrements', 'mediumIncrements', 'smallIncrements', 'tinyIncrements', 'integer', 'bigInteger', 'mediumInteger', 'smallInteger', 'tinyInteger'], true)) {
            throw new \InvalidArgumentException('MySQL auto-increment requires an integer column.');
        }
        return ' NOT NULL AUTO_INCREMENT';
    }

    /**
     * @param array $options
     * @return string
     */
    protected function _compileTableOptions(array $options): string
    {
        $sql = '';
        foreach (['engine' => 'ENGINE', 'charset' => 'DEFAULT CHARACTER SET', 'collation' => 'COLLATE', 'comment' => 'COMMENT'] as $option => $sqlName) {
            if (!isset($options[$option])) {
                continue;
            }
            if ($option === 'comment') {
                $sql .= " COMMENT='" . $this->_escapeString($options[$option]) . "'";
                continue;
            }
            if (!preg_match('/^[A-Za-z0-9_]+$/', $options[$option])) {
                throw new \InvalidArgumentException('Invalid MySQL table ' . $option . ': ' . $options[$option]);
            }
            $sql .= ' ' . $sqlName . '=' . $options[$option];
        }
        return $sql;
    }

    /**
     * @param string $value
     * @return string
     */
    protected function _escapeString(string $value): string
    {
        return str_replace("'", "''", $value);
    }
}

<?php

namespace Ddm\Db\Schema;

use Ddm\Db\Expression;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
abstract class Grammar
{

    /**
     * @return string
     */
    abstract public function getIdentifierQuote(): string;

    /**
     * @param string $type
     * @param array $options
     * @return string
     */
    abstract public function getColumnType(string $type, array $options): string;

    /**
     * @param \Ddm\Db\Schema\Column $column
     * @return string
     */
    abstract protected function _compileAutoIncrement(Column $column): string;

    /**
     * @param string $table
     * @param \Ddm\Db\Schema\Table $definition
     * @param bool $ifNotExists
     * @return string[]
     */
    public function compileCreate(string $table, Table $definition, bool $ifNotExists): array
    {
        $columns = $definition->compileColumns();
        foreach ($definition->getForeignKeys() as $foreign) {
            $columns[] = $this->compileForeignKey($table, $foreign, $definition->getTablePrefix());
        }
        $columns = array_merge($columns, $this->compileCreateTableIndexes($table, $definition));
        $sql = 'CREATE TABLE' . ($ifNotExists ? ' IF NOT EXISTS' : '') . ' ' . $this->wrap($table) . ' (' . implode(', ', $columns) . ')' . $this->_compileTableOptions($definition->getOptions());
        return array_merge([$sql], $this->compileCreateIndexes($table, $definition, $ifNotExists));
    }

    /**
     * @param string $table
     * @param bool $ifExists
     * @return string
     */
    public function compileDrop(string $table, bool $ifExists): string
    {
        return 'DROP TABLE' . ($ifExists ? ' IF EXISTS' : '') . ' ' . $this->wrap($table);
    }

    /**
     * @param string $from
     * @param string $to
     * @return string
     */
    public function compileRename(string $from, string $to): string
    {
        return 'ALTER TABLE ' . $this->wrap($from) . ' RENAME TO ' . $this->wrap($to);
    }

    /**
     * @param string $table
     * @return array
     */
    abstract public function compileHasTable(string $table): array;

    /**
     * @param string $table
     * @return array
     */
    abstract public function compileColumnListing(string $table): array;

    /**
     * @return array
     */
    abstract public function compileTableListing(): array;

    /**
     * @param string $table
     * @param \Ddm\Db\Schema\Table $definition
     * @return string[]
     */
    abstract public function compileAlter(string $table, Table $definition): array;

    /**
     * @param bool $enable
     * @return string
     */
    abstract public function compileForeignKeyChecks(bool $enable): string;

    /**
     * @param \Ddm\Db\Schema\Column $column
     * @return string
     */
    public function compileColumn(Column $column): string
    {
        $sql = $this->wrap($column->getName()) . ' ' . $this->getColumnDefinitionType($column);
        if ($column->isUnsigned()) {
            $sql .= $this->_compileUnsigned($column);
        }
        if ($column->isAutoIncrement()) {
            $sql .= $this->_compileAutoIncrement($column);
        } elseif (!$column->isNullable()) {
            $sql .= ' NOT NULL';
        }
        if ($column->hasDefault()) {
            $sql .= ' DEFAULT ' . $this->_compileDefault($column->getDefault());
        }
        return $sql;
    }

    /**
     * @param \Ddm\Db\Schema\Column $column
     * @return string
     */
    protected function getColumnDefinitionType(Column $column): string
    {
        return $this->getColumnType($column->getType(), $column->getOptions());
    }

    /**
     * @param array $columns
     * @param bool $hasAutoIncrementPrimary
     * @return string|null
     */
    public function compilePrimary(array $columns, bool $hasAutoIncrementPrimary): ?string
    {
        return 'PRIMARY KEY (' . implode(', ', $this->_wrapIdentifiers($columns)) . ')';
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
        $indexType = $unique ? 'UNIQUE ' : ($type === 'fulltext' ? 'FULLTEXT ' : ($type === 'spatial' ? 'SPATIAL ' : ''));
        return 'CREATE ' . $indexType . 'INDEX' . ($ifNotExists ? ' IF NOT EXISTS' : '') . ' ' . $this->wrap($name) . ' ON ' . $this->wrap($table) . ' (' . implode(', ', $this->_wrapIdentifiers($columns)) . ')';
    }

    /**
     * @param string $table
     * @param \Ddm\Db\Schema\Table $definition
     * @return string[]
     */
    protected function compileCreateTableIndexes(string $table, Table $definition): array
    {
        return [];
    }

    /**
     * @param string $table
     * @param \Ddm\Db\Schema\Table $definition
     * @param bool $ifNotExists
     * @return string[]
     */
    protected function compileCreateIndexes(string $table, Table $definition, bool $ifNotExists): array
    {
        $statements = [];
        foreach ($definition->getIndexDefinitions($table) as $index) {
            $statements[] = $this->compileIndex($table, $index['name'], $index['columns'], $index['unique'], $index['type'], $ifNotExists);
        }
        return $statements;
    }

    /**
     * @param string $table
     * @param \Ddm\Db\Schema\Foreign $foreign
     * @param string $tablePrefix
     * @return string
     */
    public function compileForeignKey(string $table, Foreign $foreign, string $tablePrefix): string
    {
        $columns = $foreign->getColumns();
        $references = $foreign->getReferences();
        $referencedTable = $foreign->getOnTable();
        if (!$columns || !$references || !$referencedTable || count($columns) !== count($references)) {
            throw new \InvalidArgumentException('A foreign key requires matching columns, references, and a referenced table.');
        }
        $name = $foreign->getName();
        if ($name === null) {
            $name = $table . '_' . implode('_', $columns) . '_foreign';
        }
        $sql = 'CONSTRAINT ' . $this->wrap($name) . ' FOREIGN KEY (' . implode(', ', $this->_wrapIdentifiers($columns)) . ')' . ' REFERENCES ' . $this->wrap($tablePrefix . $referencedTable) . ' (' . implode(', ', $this->_wrapIdentifiers($references)) . ')';
        if ($foreign->getOnDelete() !== null) {
            $sql .= ' ON DELETE ' . $foreign->getOnDelete();
        }
        if ($foreign->getOnUpdate() !== null) {
            $sql .= ' ON UPDATE ' . $foreign->getOnUpdate();
        }
        return $sql;
    }

    /**
     * @param string $identifier
     * @return string
     */
    public function wrap(string $identifier): string
    {
        $quote = $this->getIdentifierQuote();
        return $quote . str_replace($quote, $quote . $quote, $identifier) . $quote;
    }

    /**
     * @param array $identifiers
     * @return array
     */
    protected function _wrapIdentifiers(array $identifiers): array
    {
        $result = [];
        foreach ($identifiers as $identifier) {
            $result[] = $this->wrap($identifier);
        }
        return $result;
    }

    /**
     * @param \Ddm\Db\Schema\Column $column
     * @return string
     */
    protected function _compileUnsigned(Column $column): string
    {
        return '';
    }

    /**
     * @param array $options
     * @return string
     */
    protected function _compileTableOptions(array $options): string
    {
        if ($options) {
            throw new \InvalidArgumentException('Table engine, charset, and collation options are not supported by this database.');
        }
        return '';
    }

    /**
     * @param mixed $value
     * @return string
     */
    protected function _compileDefault(mixed $value): string
    {
        if ($value instanceof Expression) {
            return (string) $value;
        }
        if ($value === null) {
            return 'NULL';
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }
        return "'" . str_replace("'", "''", (string) $value) . "'";
    }
}

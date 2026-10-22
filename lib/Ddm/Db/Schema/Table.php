<?php

namespace Ddm\Db\Schema;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Table
{

    /** @var \Ddm\Db\Schema\Grammar */
    protected mixed $_grammar;

    /** @var \Ddm\Db\Schema\Column[] */
    protected array $_columns = [];

    /** @var array */
    protected array $_primary = [];

    /** @var array */
    protected array $_indexes = [];

    /** @var \Ddm\Db\Schema\Foreign[] */
    protected array $_foreignKeys = [];

    /** @var array */
    protected array $_commands = [];

    /** @var array */
    protected array $_options = [];

    /** @var string */
    protected mixed $_tablePrefix;

    /**
     * @param \Ddm\Db\Schema\Grammar $grammar
     * @param string $tablePrefix
     * @return void
     */
    public function __construct(Grammar $grammar, string $tablePrefix = '')
    {
        $this->_grammar = $grammar;
        $this->_tablePrefix = $tablePrefix;
    }

    /**
     * @param string $name
     * @param string|null $indexName
     * @return void
     */
    public function morphs(string $name, ?string $indexName = null): void
    {
        $this->string($name . '_type');
        $this->foreignId($name . '_id');
        $this->index([$name . '_type', $name . '_id'], $indexName);
    }

    /**
     * @param string $name
     * @param string|null $indexName
     * @return void
     */
    public function nullableMorphs(string $name, ?string $indexName = null): void
    {
        $this->string($name . '_type')->nullable();
        $this->foreignId($name . '_id')->nullable();
        $this->index([$name . '_type', $name . '_id'], $indexName);
    }

    /**
     * @param string $name
     * @param string|null $indexName
     * @return void
     */
    public function uuidMorphs(string $name, ?string $indexName = null): void
    {
        $this->string($name . '_type');
        $this->uuid($name . '_id');
        $this->index([$name . '_type', $name . '_id'], $indexName);
    }

    /**
     * @param string $name
     * @param string|null $indexName
     * @return void
     */
    public function nullableUuidMorphs(string $name, ?string $indexName = null): void
    {
        $this->string($name . '_type')->nullable();
        $this->uuid($name . '_id')->nullable();
        $this->index([$name . '_type', $name . '_id'], $indexName);
    }

    /**
     * @param string $name
     * @param string|null $indexName
     * @return void
     */
    public function ulidMorphs(string $name, ?string $indexName = null): void
    {
        $this->string($name . '_type');
        $this->ulid($name . '_id');
        $this->index([$name . '_type', $name . '_id'], $indexName);
    }

    /**
     * @param string $name
     * @param string|null $indexName
     * @return void
     */
    public function nullableUlidMorphs(string $name, ?string $indexName = null): void
    {
        $this->string($name . '_type')->nullable();
        $this->ulid($name . '_id')->nullable();
        $this->index([$name . '_type', $name . '_id'], $indexName);
    }

    /**
     * @param string $name
     * @return \Ddm\Db\Schema\Column
     */
    public function increments(string $name): Column
    {
        return $this->_addColumn($name, 'increments')->unsigned()->autoIncrement()->primary();
    }

    /**
     * @param string $name
     * @return \Ddm\Db\Schema\Column
     */
    public function tinyIncrements(string $name): Column
    {
        return $this->_addColumn($name, 'tinyIncrements')->unsigned()->autoIncrement()->primary();
    }

    /**
     * @param string $name
     * @return \Ddm\Db\Schema\Column
     */
    public function smallIncrements(string $name): Column
    {
        return $this->_addColumn($name, 'smallIncrements')->unsigned()->autoIncrement()->primary();
    }

    /**
     * @param string $name
     * @return \Ddm\Db\Schema\Column
     */
    public function mediumIncrements(string $name): Column
    {
        return $this->_addColumn($name, 'mediumIncrements')->unsigned()->autoIncrement()->primary();
    }

    /**
     * @param string $name
     * @return \Ddm\Db\Schema\Column
     */
    public function bigIncrements(string $name): Column
    {
        return $this->_addColumn($name, 'bigIncrements')->unsigned()->autoIncrement()->primary();
    }

    /**
     * @param string $name
     * @return \Ddm\Db\Schema\Column
     */
    public function id(string $name = 'id'): Column
    {
        return $this->increments($name);
    }

    /**
     * @param string $name
     * @return \Ddm\Db\Schema\Column
     */
    public function integer(string $name): Column
    {
        return $this->_addColumn($name, 'integer');
    }

    /**
     * @param string $name
     * @return \Ddm\Db\Schema\Column
     */
    public function bigInteger(string $name): Column
    {
        return $this->_addColumn($name, 'bigInteger');
    }

    /**
     * @param string $name
     * @return \Ddm\Db\Schema\Column
     */
    public function smallInteger(string $name): Column
    {
        return $this->_addColumn($name, 'smallInteger');
    }

    /**
     * @param string $name
     * @return \Ddm\Db\Schema\Column
     */
    public function mediumInteger(string $name): Column
    {
        return $this->_addColumn($name, 'mediumInteger');
    }

    /**
     * @param string $name
     * @return \Ddm\Db\Schema\Column
     */
    public function tinyInteger(string $name): Column
    {
        return $this->_addColumn($name, 'tinyInteger');
    }

    /**
     * @param string $name
     * @param int $length
     * @return \Ddm\Db\Schema\Column
     */
    public function string(string $name, int $length = 255): Column
    {
        return $this->_addColumn($name, 'string', ['length' => $length]);
    }

    /**
     * @param string $name
     * @return \Ddm\Db\Schema\Column
     */
    public function text(string $name): Column
    {
        return $this->_addColumn($name, 'text');
    }

    /**
     * @param string $name
     * @return \Ddm\Db\Schema\Column
     */
    public function tinyText(string $name): Column
    {
        return $this->_addColumn($name, 'tinyText');
    }

    /**
     * @param string $name
     * @return \Ddm\Db\Schema\Column
     */
    public function mediumText(string $name): Column
    {
        return $this->_addColumn($name, 'mediumText');
    }

    /**
     * @param string $name
     * @return \Ddm\Db\Schema\Column
     */
    public function longText(string $name): Column
    {
        return $this->_addColumn($name, 'longText');
    }

    /**
     * @param string $name
     * @return \Ddm\Db\Schema\Column
     */
    public function boolean(string $name): Column
    {
        return $this->_addColumn($name, 'boolean');
    }

    /**
     * @param string $name
     * @return \Ddm\Db\Schema\Column
     */
    public function date(string $name): Column
    {
        return $this->_addColumn($name, 'date');
    }

    /**
     * @param string $name
     * @return \Ddm\Db\Schema\Column
     */
    public function time(string $name): Column
    {
        return $this->_addColumn($name, 'time');
    }

    /**
     * @param string $name
     * @return \Ddm\Db\Schema\Column
     */
    public function dateTime(string $name): Column
    {
        return $this->_addColumn($name, 'dateTime');
    }

    /**
     * @param string $name
     * @return \Ddm\Db\Schema\Column
     */
    public function timestamp(string $name): Column
    {
        return $this->_addColumn($name, 'timestamp');
    }

    /**
     * @param string $name
     * @param int $precision
     * @return \Ddm\Db\Schema\Column
     */
    public function timestampTz(string $name, int $precision = 0): Column
    {
        return $this->_addColumn($name, 'timestampTz', ['precision' => $precision]);
    }

    /**
     * @param string $name
     * @param int $precision
     * @return \Ddm\Db\Schema\Column
     */
    public function dateTimeTz(string $name, int $precision = 0): Column
    {
        return $this->_addColumn($name, 'dateTimeTz', ['precision' => $precision]);
    }

    /**
     * @param string $name
     * @param int $precision
     * @return \Ddm\Db\Schema\Column
     */
    public function timeTz(string $name, int $precision = 0): Column
    {
        return $this->_addColumn($name, 'timeTz', ['precision' => $precision]);
    }

    /**
     * @param string $name
     * @return \Ddm\Db\Schema\Column
     */
    public function foreignUuid(string $name): Column
    {
        return $this->uuid($name);
    }

    /**
     * @param string $name
     * @return \Ddm\Db\Schema\Column
     */
    public function foreignUlid(string $name): Column
    {
        return $this->ulid($name);
    }

    /**
     * @param string $name
     * @param int $precision
     * @param int $scale
     * @return \Ddm\Db\Schema\Column
     */
    public function float(string $name, int $precision = 8, int $scale = 2): Column
    {
        return $this->_addColumn($name, 'float', ['precision' => $precision, 'scale' => $scale]);
    }

    /**
     * @param string $name
     * @param int $precision
     * @param int $scale
     * @return \Ddm\Db\Schema\Column
     */
    public function double(string $name, int $precision = 15, int $scale = 2): Column
    {
        return $this->_addColumn($name, 'double', ['precision' => $precision, 'scale' => $scale]);
    }

    /**
     * @param string $name
     * @param int $precision
     * @param int $scale
     * @return \Ddm\Db\Schema\Column
     */
    public function decimal(string $name, int $precision = 8, int $scale = 2): Column
    {
        return $this->_addColumn($name, 'decimal', ['precision' => $precision, 'scale' => $scale]);
    }

    /**
     * @param string $name
     * @return \Ddm\Db\Schema\Column
     */
    public function binary(string $name): Column
    {
        return $this->_addColumn($name, 'binary');
    }

    /**
     * @param string $name
     * @param int $length
     * @return \Ddm\Db\Schema\Column
     */
    public function char(string $name, int $length = 255): Column
    {
        return $this->_addColumn($name, 'char', ['length' => $length]);
    }

    /**
     * @param string $name
     * @return \Ddm\Db\Schema\Column
     */
    public function uuid(string $name): Column
    {
        return $this->_addColumn($name, 'uuid');
    }

    /**
     * @param string $name
     * @return \Ddm\Db\Schema\Column
     */
    public function ulid(string $name): Column
    {
        return $this->_addColumn($name, 'ulid');
    }

    /**
     * @param string $name
     * @return \Ddm\Db\Schema\Column
     */
    public function json(string $name): Column
    {
        return $this->_addColumn($name, 'json');
    }

    /**
     * @param string $name
     * @return \Ddm\Db\Schema\Column
     */
    public function jsonb(string $name): Column
    {
        return $this->_addColumn($name, 'jsonb');
    }

    /**
     * @param string $name
     * @return \Ddm\Db\Schema\Column
     */
    public function year(string $name): Column
    {
        return $this->_addColumn($name, 'year');
    }

    /**
     * @param string $name
     * @param string[] $allowed
     * @return \Ddm\Db\Schema\Column
     */
    public function enumeration(string $name, array $allowed): Column
    {
        return $this->_addColumn($name, 'enum', ['allowed' => $allowed]);
    }

    /**
     * @param string $name
     * @param string[] $allowed
     * @return \Ddm\Db\Schema\Column
     */
    public function enum(string $name, array $allowed): Column
    {
        return $this->enumeration($name, $allowed);
    }

    /**
     * @param string $name
     * @return \Ddm\Db\Schema\Column
     */
    public function foreignId(string $name): Column
    {
        return $this->unsignedBigInteger($name);
    }

    /**
     * @param string $name
     * @param string $table
     * @param string $column
     * @param string|null $indexName
     * @return \Ddm\Db\Schema\Foreign
     */
    public function constrainedForeignId(string $name, string $table, string $column = 'id', ?string $indexName = null): Foreign
    {
        return $this->foreignId($name)->constrained($table, $column, $indexName);
    }

    /**
     * @param string $name
     * @return \Ddm\Db\Schema\Column
     */
    public function unsignedBigInteger(string $name): Column
    {
        return $this->bigInteger($name)->unsigned();
    }

    /**
     * @param string $name
     * @return \Ddm\Db\Schema\Column
     */
    public function unsignedInteger(string $name): Column
    {
        return $this->integer($name)->unsigned();
    }

    /**
     * @param string $name
     * @return \Ddm\Db\Schema\Column
     */
    public function unsignedTinyInteger(string $name): Column
    {
        return $this->tinyInteger($name)->unsigned();
    }

    /**
     * @param string $name
     * @return \Ddm\Db\Schema\Column
     */
    public function unsignedSmallInteger(string $name): Column
    {
        return $this->smallInteger($name)->unsigned();
    }

    /**
     * @param string $name
     * @return \Ddm\Db\Schema\Column
     */
    public function unsignedMediumInteger(string $name): Column
    {
        return $this->mediumInteger($name)->unsigned();
    }

    /**
     * @param string $name
     * @return \Ddm\Db\Schema\Column
     */
    public function softDeletes(string $name = 'deleted_at'): Column
    {
        return $this->timestamp($name)->nullable();
    }

    /**
     * @param string $name
     * @return \Ddm\Db\Schema\Column
     */
    public function softDeletesTz(string $name = 'deleted_at'): Column
    {
        return $this->timestampTz($name)->nullable();
    }

    /**
     * @param string $name
     * @return \Ddm\Db\Schema\Column
     */
    public function rememberToken(string $name = 'remember_token'): Column
    {
        return $this->string($name, 100)->nullable();
    }

    /**
     * 添加Laravel风格的created_at和updated_at字段
     * @return void
     */
    public function timestamps(): void
    {
        $this->timestamp('created_at')->nullable();
        $this->timestamp('updated_at')->nullable();
    }

    /**
     * @return void
     */
    public function timestampsTz(): void
    {
        $this->timestampTz('created_at')->nullable();
        $this->timestampTz('updated_at')->nullable();
    }

    /**
     * @return void
     */
    public function nullableTimestamps(): void
    {
        $this->timestamps();
    }

    /**
     * @return void
     */
    public function nullableTimestampsTz(): void
    {
        $this->timestampsTz();
    }

    /**
     * @param string|array $columns
     * @return $this
     */
    public function primary(string|array $columns): static
    {
        $this->_primary = array_merge($this->_primary, (array) $columns);
        return $this;
    }

    /**
     * @param string|array $columns
     * @param string|null $name
     * @return $this
     */
    public function unique(string|array $columns, ?string $name = null): static
    {
        $this->_indexes[] = ['columns' => (array) $columns, 'unique' => true, 'name' => $name, 'type' => 'unique'];
        return $this;
    }

    /**
     * @param string|array $columns
     * @param string|null $name
     * @return $this
     */
    public function index(string|array $columns, ?string $name = null): static
    {
        $this->_indexes[] = ['columns' => (array) $columns, 'unique' => false, 'name' => $name, 'type' => 'index'];
        return $this;
    }

    /**
     * @param string|array $columns
     * @param string|null $name
     * @return $this
     */
    public function fullText(string|array $columns, ?string $name = null): static
    {
        $this->_indexes[] = ['columns' => (array) $columns, 'unique' => false, 'name' => $name, 'type' => 'fulltext'];
        return $this;
    }

    /**
     * @param string|array $columns
     * @param string|null $name
     * @return $this
     */
    public function spatialIndex(string|array $columns, ?string $name = null): static
    {
        $this->_indexes[] = ['columns' => (array) $columns, 'unique' => false, 'name' => $name, 'type' => 'spatial'];
        return $this;
    }

    /**
     * @param string|array $columns
     * @param string|null $name
     * @return \Ddm\Db\Schema\Foreign
     */
    public function foreign(string|array $columns, ?string $name = null): Foreign
    {
        $foreign = new Foreign((array) $columns, $name);
        $this->_foreignKeys[] = $foreign;
        return $foreign;
    }

    /**
     * @param string $engine
     * @return $this
     */
    public function engine(string $engine): static
    {
        $this->_options['engine'] = $engine;
        return $this;
    }

    /**
     * @param string $charset
     * @return $this
     */
    public function charset(string $charset): static
    {
        $this->_options['charset'] = $charset;
        return $this;
    }

    /**
     * @param string $collation
     * @return $this
     */
    public function collation(string $collation): static
    {
        $this->_options['collation'] = $collation;
        return $this;
    }

    /**
     * @param string $comment
     * @return $this
     */
    public function comment(string $comment): static
    {
        $this->_options['comment'] = $comment;
        return $this;
    }

    /**
     * @param string|array $columns
     * @return $this
     */
    public function dropColumn(string|array $columns): static
    {
        $this->_commands[] = ['name' => 'dropColumn', 'columns' => (array) $columns];
        return $this;
    }

    /**
     * @param string $from
     * @param string $to
     * @return $this
     */
    public function renameColumn(string $from, string $to): static
    {
        $this->_commands[] = ['name' => 'renameColumn', 'from' => $from, 'to' => $to];
        return $this;
    }

    /**
     * @return \Ddm\Db\Schema\Table
     */
    public function dropTimestamps(): Table
    {
        return $this->dropColumn(['created_at', 'updated_at']);
    }

    /**
     * @param string $column
     * @return \Ddm\Db\Schema\Table
     */
    public function dropSoftDeletes(string $column = 'deleted_at'): Table
    {
        return $this->dropColumn($column);
    }

    /**
     * @param string $name
     * @param string|null $indexName
     * @return \Ddm\Db\Schema\Table
     */
    public function dropMorphs(string $name, ?string $indexName = null): Table
    {
        $columns = [$name . '_type', $name . '_id'];
        $this->dropIndex($columns, $indexName);
        return $this->dropColumn($columns);
    }

    /**
     * @param string $name
     * @param string|null $indexName
     * @return \Ddm\Db\Schema\Table
     */
    public function dropUuidMorphs(string $name, ?string $indexName = null): Table
    {
        return $this->dropMorphs($name, $indexName);
    }

    /**
     * @param string $name
     * @param string|null $indexName
     * @return \Ddm\Db\Schema\Table
     */
    public function dropUlidMorphs(string $name, ?string $indexName = null): Table
    {
        return $this->dropMorphs($name, $indexName);
    }

    /**
     * @param string|null $name
     * @return $this
     */
    public function dropPrimary(?string $name = null): static
    {
        $this->_commands[] = ['name' => 'dropPrimary', 'index' => $name];
        return $this;
    }

    /**
     * @param string|array $columns
     * @param string|null $name
     * @return $this
     */
    public function dropUnique(string|array $columns, ?string $name = null): static
    {
        $this->_commands[] = ['name' => 'dropUnique', 'columns' => $name === null && is_string($columns) ? [] : (array) $columns, 'index' => $name === null && is_string($columns) ? $columns : $name, 'indexType' => 'unique'];
        return $this;
    }

    /**
     * @param string|array $columns
     * @param string|null $name
     * @return $this
     */
    public function dropIndex(string|array $columns, ?string $name = null): static
    {
        $this->_commands[] = ['name' => 'dropIndex', 'columns' => $name === null && is_string($columns) ? [] : (array) $columns, 'index' => $name === null && is_string($columns) ? $columns : $name, 'indexType' => 'index'];
        return $this;
    }

    /**
     * @param string|array $columns
     * @param string|null $name
     * @return $this
     */
    public function dropFullText(string|array $columns, ?string $name = null): static
    {
        $this->_commands[] = ['name' => 'dropIndex', 'columns' => $name === null && is_string($columns) ? [] : (array) $columns, 'index' => $name === null && is_string($columns) ? $columns : $name, 'indexType' => 'fulltext'];
        return $this;
    }

    /**
     * @param string|array $columns
     * @param string|null $name
     * @return $this
     */
    public function dropSpatialIndex(string|array $columns, ?string $name = null): static
    {
        $this->_commands[] = ['name' => 'dropIndex', 'columns' => $name === null && is_string($columns) ? [] : (array) $columns, 'index' => $name === null && is_string($columns) ? $columns : $name, 'indexType' => 'spatial'];
        return $this;
    }

    /**
     * @param string|array $columns
     * @param string|null $name
     * @return $this
     */
    public function dropForeign(string|array $columns, ?string $name = null): static
    {
        $this->_commands[] = ['name' => 'dropForeign', 'columns' => $name === null && is_string($columns) ? [] : (array) $columns, 'index' => $name === null && is_string($columns) ? $columns : $name, 'indexType' => 'foreign'];
        return $this;
    }

    /**
     * @param string $from
     * @param string $to
     * @return $this
     */
    public function renameIndex(string $from, string $to): static
    {
        $this->_commands[] = ['name' => 'renameIndex', 'from' => $from, 'to' => $to];
        return $this;
    }

    /**
     * @param string|array $columns
     * @return \Ddm\Db\Schema\Table
     */
    public function dropConstrainedForeignId(string|array $columns): Table
    {
        $columns = (array) $columns;
        $this->dropForeign($columns);
        return $this->dropColumn($columns);
    }

    /**
     * @return array
     */
    public function compileColumns(): array
    {
        $columns = [];
        foreach ($this->_columns as $column) {
            $columns[] = $this->_grammar->compileColumn($column);
            if ($column->isPrimary() && !in_array($column->getName(), $this->_primary, true)) {
                $this->_primary[] = $column->getName();
            }
        }
        if ($this->_primary) {
            $primary = $this->_grammar->compilePrimary($this->_primary, $this->_hasAutoIncrementPrimary());
            if ($primary !== null) {
                $columns[] = $primary;
            }
        }
        return $columns;
    }

    /**
     * @return \Ddm\Db\Schema\Column[]
     */
    public function getColumns(): Column
    {
        return $this->_columns;
    }

    /**
     * @return \Ddm\Db\Schema\Foreign[]
     */
    public function getForeignKeys(): Foreign
    {
        return $this->_foreignKeys;
    }

    /**
     * @return array
     */
    public function getCommands(): array
    {
        return $this->_commands;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->_options;
    }

    /**
     * @return string
     */
    public function getTablePrefix(): string
    {
        return $this->_tablePrefix;
    }

    /**
     * @param string $table
     * @return array
     */
    public function compileIndexes(string $table): array
    {
        $queries = [];
        foreach ($this->getIndexDefinitions($table) as $index) {
            $name = $index['name'];
            $queries[] = $this->_grammar->compileIndex($table, $name, $index['columns'], $index['unique'], $index['type']);
        }
        return $queries;
    }

    /**
     * @param string $table
     * @return array
     */
    public function getIndexDefinitions(string $table): array
    {
        $indexes = $this->_indexes;
        foreach ($this->_columns as $column) {
            if ($column->isUnique() || $column->hasIndex()) {
                $indexes[] = ['columns' => [$column->getName()], 'unique' => $column->isUnique(), 'name' => null, 'type' => $column->isUnique() ? 'unique' : 'index'];
            }
        }
        foreach ($indexes as &$index) {
            if ($index['name'] === null) {
                $suffix = $index['type'] === 'fulltext' ? '_fulltext' : ($index['type'] === 'spatial' ? '_spatialindex' : ($index['unique'] ? '_unique' : '_index'));
                $index['name'] = $table . '_' . implode('_', $index['columns']) . $suffix;
            }
        }
        unset($index);
        return $indexes;
    }

    /**
     * @param string $name
     * @param string $type
     * @param array $options
     * @return \Ddm\Db\Schema\Column
     */
    protected function _addColumn(string $name, string $type, array $options = []): Column
    {
        $column = new Column($name, $type, $options, $this);
        $this->_columns[] = $column;
        return $column;
    }

    /**
     * @return bool
     */
    protected function _hasAutoIncrementPrimary(): bool
    {
        foreach ($this->_columns as $column) {
            if ($column->isAutoIncrement() && $column->isPrimary()) {
                return true;
            }
        }
        return false;
    }
}

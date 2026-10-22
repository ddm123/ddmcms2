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
class Column
{

    /** @var string */
    protected mixed $_name;

    /** @var string */
    protected mixed $_type;

    /** @var array */
    protected mixed $_options;

    /** @var bool */
    protected bool $_nullable = false;

    /** @var bool */
    protected bool $_unsigned = false;

    /** @var bool */
    protected bool $_autoIncrement = false;

    /** @var bool */
    protected bool $_primary = false;

    /** @var bool */
    protected bool $_unique = false;

    /** @var bool */
    protected bool $_index = false;

    /** @var bool */
    protected bool $_hasDefault = false;

    /** @var mixed */
    protected mixed $_default;

    /** @var bool */
    protected bool $_change = false;

    /** @var \Ddm\Db\Schema\Table|null */
    protected mixed $_table;

    /**
     * @param string $name
     * @param string $type
     * @param array $options
     * @param \Ddm\Db\Schema\Table|null $table
     * @return void
     */
    public function __construct(string $name, string $type, array $options = [], ?Table $table = null)
    {
        $this->_name = $name;
        $this->_type = $type;
        $this->_options = $options;
        $this->_table = $table;
    }

    /**
     * @param bool $value
     * @return $this
     */
    public function nullable(bool $value = true): static
    {
        $this->_nullable = (bool) $value;
        return $this;
    }

    /**
     * @return $this
     */
    public function unsigned(): static
    {
        $this->_unsigned = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function autoIncrement(): static
    {
        $this->_autoIncrement = true;
        $this->_primary = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function primary(): static
    {
        $this->_primary = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function unique(): static
    {
        $this->_unique = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function index(): static
    {
        $this->_index = true;
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
     * @return $this
     */
    public function first(): static
    {
        $this->_options['first'] = true;
        return $this;
    }

    /**
     * @param string $column
     * @return $this
     */
    public function after(string $column): static
    {
        $this->_options['after'] = $column;
        return $this;
    }

    /**
     * @return \Ddm\Db\Schema\Column
     */
    public function useCurrent(): Column
    {
        return $this->defaultValue(new Expression('CURRENT_TIMESTAMP'));
    }

    /**
     * @return $this
     */
    public function useCurrentOnUpdate(): static
    {
        $this->_options['useCurrentOnUpdate'] = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function change(): static
    {
        $this->_change = true;
        return $this;
    }

    /**
     * @param string|null $table
     * @param string|null $column
     * @param string|null $indexName
     * @return \Ddm\Db\Schema\Foreign
     */
    public function constrained(?string $table = null, ?string $column = 'id', ?string $indexName = null): Foreign
    {
        if (!$this->_table) {
            throw new \LogicException('The column is not attached to a schema table definition.');
        }
        if ($table === null) {
            $base = preg_replace('/_id$/', '', $this->_name);
            if (preg_match('/[^aeiou]y$/i', $base)) {
                $table = substr($base, 0, -1) . 'ies';
            } elseif (preg_match('/(s|x|z|ch|sh)$/i', $base)) {
                $table = $base . 'es';
            } else {
                $table = $base . 's';
            }
        }
        return $this->_table->foreign($this->_name, $indexName)->references($column)->on($table);
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->_type;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->_options;
    }

    /**
     * @return bool
     */
    public function isChange(): bool
    {
        return $this->_change;
    }

    /**
     * @return bool
     */
    public function isNullable(): bool
    {
        return $this->_nullable;
    }

    /**
     * @return bool
     */
    public function isUnsigned(): bool
    {
        return $this->_unsigned;
    }

    /**
     * @return bool
     */
    public function hasDefault(): bool
    {
        return $this->_hasDefault;
    }

    /**
     * @return mixed
     */
    public function getDefault(): mixed
    {
        return $this->_default;
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function defaultValue(mixed $value): static
    {
        $this->_default = $value;
        $this->_hasDefault = true;
        return $this;
    }

    /**
     * Laravel风格的default别名
     * @param mixed $value
     * @return \Ddm\Db\Schema\Column
     */
    public function default(mixed $value): Column
    {
        return $this->defaultValue($value);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->_name;
    }

    /**
     * @return bool
     */
    public function isPrimary(): bool
    {
        return $this->_primary;
    }

    /**
     * @return bool
     */
    public function isUnique(): bool
    {
        return $this->_unique;
    }

    /**
     * @return bool
     */
    public function hasIndex(): bool
    {
        return $this->_index;
    }

    /**
     * @return bool
     */
    public function isAutoIncrement(): bool
    {
        return $this->_autoIncrement;
    }
}

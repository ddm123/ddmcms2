<?php

namespace Ddm\Db\Schema;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Foreign
{

    /** @var string[] */
    protected array $_columns = [];

    /** @var string|null */
    protected ?string $_name = null;

    /** @var string[] */
    protected array $_references = [];

    /** @var string|null */
    protected ?string $_onTable = null;

    /** @var string|null */
    protected ?string $_onDelete = null;

    /** @var string|null */
    protected ?string $_onUpdate = null;

    /**
     * @param string[] $columns
     * @param string|null $name
     * @return void
     */
    public function __construct(array $columns, ?string $name = null)
    {
        $this->_columns = $columns;
        $this->_name = $name;
    }

    /**
     * @param string|array $columns
     * @return $this
     */
    public function references(string|array $columns): static
    {
        $this->_references = (array) $columns;
        return $this;
    }

    /**
     * @param string $table
     * @return $this
     */
    public function on(string $table): static
    {
        $this->_onTable = $table;
        return $this;
    }

    /**
     * @param string $action
     * @return $this
     */
    public function onDelete(string $action): static
    {
        $this->_onDelete = $this->_normalizeAction($action);
        return $this;
    }

    /**
     * @param string $action
     * @return $this
     */
    public function onUpdate(string $action): static
    {
        $this->_onUpdate = $this->_normalizeAction($action);
        return $this;
    }

    /**
     * @return string[]
     */
    public function getColumns(): array
    {
        return $this->_columns;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->_name;
    }

    /**
     * @return string[]
     */
    public function getReferences(): array
    {
        return $this->_references;
    }

    /**
     * @return string|null
     */
    public function getOnTable(): ?string
    {
        return $this->_onTable;
    }

    /**
     * @return string|null
     */
    public function getOnDelete(): ?string
    {
        return $this->_onDelete;
    }

    /**
     * @return string|null
     */
    public function getOnUpdate(): ?string
    {
        return $this->_onUpdate;
    }

    /**
     * @param string $action
     * @return string
     */
    protected function _normalizeAction(string $action): string
    {
        $action = strtoupper(trim($action));
        $allowed = ['CASCADE', 'RESTRICT', 'SET NULL', 'NO ACTION', 'SET DEFAULT'];
        if (!in_array($action, $allowed, true)) {
            throw new \InvalidArgumentException('Unsupported foreign key action: ' . $action);
        }
        return $action;
    }
}

<?php

namespace Core\Model;

use Ddm;
use Ddm\DataObject;
use Ddm\Db;
use Ddm\Db\Builder;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
abstract class AbstractModel extends DataObject
{

    /** @var \Core\Model\Resource\AbstractModel|null */
    protected mixed $_resource = null;

    /** @var bool|null */
    protected mixed $_isLoaded = null;

    /** @var bool */
    protected bool $_isDeleted = false;

    /**
     * @return $this
     */
    protected function _beforeLoad(mixed $id, mixed $field = null): static
    {
        return $this;
    }

    /**
     * @return $this
     */
    protected function _afterLoad(): static
    {
        return $this;
    }

    /**
     * @return $this
     */
    protected function _beforeSave(): static
    {
        return $this;
    }

    /**
     * @return $this
     */
    protected function _afterSave(): static
    {
        return $this;
    }

    /**
     * @return $this
     */
    protected function _beforeDelete(): static
    {
        return $this;
    }

    /**
     * @return $this
     */
    protected function _afterDelete(): static
    {
        return $this;
    }

    /**
     * @return \Core\Model\Resource\AbstractModel
     */
    public function getResource(): Resource\AbstractModel
    {
        if ($this->_resource === null) {
            $resourceClass = str_replace('\Model\\', '\Model\Resource\\', get_class($this));
            $this->_resource = new $resourceClass();
            $this->_resource->setModelObject($this);
        }
        return $this->_resource;
    }

    /**
     * @return Builder
     */
    public function getSelect(): Builder
    {
        return $this->getResource()->getSelect();
    }

    /**
     * @param int $rowCount 指定返回的行数的最大值
     * @param int $offset 第几行开始
     * @return $this
     */
    public function setLimit(int $rowCount, int $offset = 0): static
    {
        $this->getSelect()->limit((int) $rowCount, (int) $offset);
        return $this;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->getResource()->getPrimarykey() ? $this->getData($this->getResource()->getPrimarykey()) : null;
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setId(int $id): static
    {
        if ($this->getResource()->getPrimarykey()) {
            $this->setData($this->getResource()->getPrimarykey(), $id);
        }
        return $this;
    }

    /**
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->_isDeleted;
    }

    /**
     * @param int $id
     * @param string|null $field
     * @return \Core\Model\AbstractModel
     */
    public function load(int $id, ?string $field = null): AbstractModel
    {
        if ($this->_isLoaded != "{$id}-{$field}") {
            Ddm::dispatchEvent('model_load_before', ['object' => $this, 'field' => $field, 'value' => $id]);
            $this->_beforeLoad($id, $field);
            $this->getResource()->load($this, $id, $field);
            Ddm::dispatchEvent('model_load_after', ['object' => $this]);
            $this->_afterLoad();
            $this->_isLoaded = "{$id}-{$field}";
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function save(): static
    {
        if ($this->isDeleted()) {
            return $this;
        }
        Db::lockWriteConn();
        Ddm::dispatchEvent('model_save_before', ['object' => $this]);
        $this->_beforeSave();
        $this->getResource()->save($this);
        Ddm::dispatchEvent('model_save_after', ['object' => $this]);
        $this->_afterSave();
        Db::unLockReadWite();
        return $this;
    }

    /**
     * @param int|null $id
     * @param string|null $field
     * @return $this
     */
    public function delete(?int $id = null, ?string $field = null): static
    {
        if ($this->isDeleted()) {
            return $this;
        }
        Db::lockWriteConn();
        Ddm::dispatchEvent('model_delete_before', ['object' => $this]);
        $this->_beforeDelete();
        $this->getResource()->delete($this, $id, $field);
        $this->_isDeleted = true;
        Ddm::dispatchEvent('model_delete_after', ['object' => $this]);
        $this->_afterDelete();
        Db::unLockReadWite();
        return $this;
    }
}

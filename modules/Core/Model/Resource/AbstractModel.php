<?php

namespace Core\Model\Resource;

use Core\Model\AbstractModel as ModelAbstractModel;
use Ddm\Db;
use Ddm\Db\Builder;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
abstract class AbstractModel
{

    private mixed $_mainTableName = null;

    private string $_primarykey = 'id';

    protected array $_fields = [];

    protected mixed $_select = null;

    protected mixed $_modelObject = null;

    /**
     * 必须使用该方法调用$this->_setTable($tableName,$primarykey);
     * @return $this
     */
    abstract protected function _init(): static;

    public function __construct()
    {
        $this->_init();
    }

    /**
     * @param string $mainTable
     * @param string $field
     * @param mixed $value
     * @return Builder
     */
    protected function _getLoadSelect(string $mainTable, string $field, mixed $value): Builder
    {
        $select = Db::table(['main_table' => $mainTable]);
        $select->where($field, '=', $value);
        return $select;
    }

    /**
     * @return Builder
     */
    protected function _getSelect(): Builder
    {
        $this->_select = Db::table(['main_table' => $this->getMainTableName()]);
        $this->_select->select(['main_table.*']);
        return $this->_select;
    }

    /**
     * @param ModelAbstractModel $object
     * @return array
     */
    protected function _getSaveData(ModelAbstractModel $object): array
    {
        $dataValue = [];
        foreach ($this->getFields($this->_mainTableName) as $field) {
            if ($object->issetData($field) && $object->{$field} != $object->getOrigData($field)) {
                $dataValue[$field] = $object->{$field};
            }
        }
        return $dataValue;
    }

    /**
     * 设置表名和主键字段名
     * @param string $tableName
     * @param string $primarykey
     * @return $this
     */
    protected function _setTable(string $tableName, string $primarykey = 'id'): static
    {
        $this->_mainTableName = (string) $tableName;
        $this->_primarykey = (string) $primarykey;
        return $this;
    }

    /**
     * @param ModelAbstractModel $object
     * @return $this
     */
    protected function _beforeSave(ModelAbstractModel $object): static
    {
        return $this;
    }

    /**
     * @param ModelAbstractModel $object
     * @return $this
     */
    protected function _afterSave(ModelAbstractModel $object): static
    {
        return $this;
    }

    /**
     * @param ModelAbstractModel $object
     * @return $this
     */
    protected function _beforeDelete(ModelAbstractModel $object): static
    {
        return $this;
    }

    /**
     * @param ModelAbstractModel $object
     * @return $this
     */
    protected function _afterDelete(ModelAbstractModel $object): static
    {
        return $this;
    }

    /**
     * 获取主表完整的表名(包含表前缀,如果有的话)
     * @return string
     */
    public function getMainTable(): string
    {
        return Db::getTable($this->_mainTableName);
    }

    /**
     * @return string
     */
    public function getMainTableName(): string
    {
        return $this->_mainTableName;
    }

    /**
     * 获取主表的主键字段名称
     * @return string
     */
    public function getPrimarykey(): string
    {
        return $this->_primarykey;
    }

    /**
     * 获取字段列表
     * @param string $table
     * @return string[]
     */
    public function getFields(string $table): string
    {
        if ($table == '') {
            throw new \InvalidArgumentException('$table parameter is empty');
        }
        if (!isset($this->_fields[$table])) {
            $this->_fields[$table] = Db::schema()->getColumnListing($table);
        }
        return $this->_fields[$table];
    }

    /**
     * @param ModelAbstractModel $modelObject
     * @return $this
     */
    public function setModelObject(ModelAbstractModel $modelObject): static
    {
        $this->_modelObject = $modelObject;
        return $this;
    }

    /**
     * @return ModelAbstractModel
     */
    public function getModelObject(): ModelAbstractModel
    {
        return $this->_modelObject;
    }

    /**
     * @param ModelAbstractModel $object
     * @param mixed $value
     * @param string $field
     * @return \Core\Model\Resource\AbstractModel
     */
    public function load(ModelAbstractModel $object, mixed $value, ?string $field = null): AbstractModel
    {
        if ($field === null) {
            $field = "main_table.{$this->_primarykey}";
        }
        $select = $this->_getLoadSelect($this->_mainTableName, $field, $value);
        if ($result = $select->first()) {
            $object->addData($result);
            $object->setOrigData(null, null, true);
        }
        return $this;
    }

    /**
     * @return Builder
     */
    public function getSelect(): Builder
    {
        return $this->_select === null ? $this->_getSelect() : $this->_select;
    }

    /**
     * @return $this
     */
    public function save(ModelAbstractModel $object): static
    {
        Db::lockWriteConn();
        $this->_beforeSave($object);
        $dataValue = $this->_getSaveData($object);
        if ($object->getId()) {
            unset($dataValue[$this->_primarykey]);
            if ($dataValue) {
                Db::table($this->getMainTableName())->where($this->_primarykey, '=', $object->getId())->update($dataValue);
            }
        } else {
            $object->setId(Db::table($this->getMainTableName())->insertGetId($dataValue));
        }
        $this->_afterSave($object);
        $object->setOrigData(null, null, true);
        Db::unLockReadWite();
        return $this;
    }

    /**
     * @param ModelAbstractModel $object
     * @param mixed $value
     * @param string $field
     * @return $this
     */
    public function delete(ModelAbstractModel $object, mixed $value, ?string $field = null): static
    {
        if ($field === null) {
            $field = $this->_primarykey;
        }
        if (!$value) {
            $value = $object->getId();
        }
        if (!$value || !$field) {
            throw new \Exception('Can not find the records to be deleted.');
        }
        if ($field == $this->_primarykey && !$object->getId()) {
            $object->setId($value);
        }
        Db::lockWriteConn();
        $this->_beforeDelete($object);
        Db::table($this->getMainTableName())->where($field, '=', $value)->delete();
        $this->_afterDelete($object);
        Db::unLockReadWite();
        return $this;
    }
}

<?php

namespace Core\Model;

use Ddm;
use Ddm\Db;
use Ddm\Db\Builder;
use Ddm\Db\Expression;
use Ddm\Db\JoinClause;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
/**
 * @method \Core\Model\Resource\Entity|\Core\Model\Resource\AbstractModel getResource()
 */
abstract class Entity extends AbstractModel
{

    protected static array $_attributes = [];

    protected mixed $_attributeSelect = null;

    protected array $_attributesValue = [];

    protected int $_entity = 0;

    protected mixed $_entityPrimarykey = null;

    protected array $_useDefaultValue = [];

    protected array $_selectedAttributes = [];
    abstract protected function _initEntity(): void;

    public function __construct()
    {
        parent::__construct();
        $this->_initEntity();
    }

    /**
     * @param string $entity
     * @return $this
     */
    public function setEntity(string $entity): static
    {
        $this->_entity = $entity;
        return $this;
    }

    /**
     * @return string
     */
    public function getEntity(): string
    {
        return $this->_entity;
    }

    /**
     * @param string $entityPrimarykey
     * @return $this
     */
    public function setEntityPrimarykey(string $entityPrimarykey): static
    {
        $this->_entityPrimarykey = $entityPrimarykey;
        return $this;
    }

    /**
     * @return string
     */
    public function getEntityPrimarykey(): string
    {
        return $this->_entityPrimarykey ? $this->_entityPrimarykey : $this->_entity . '_id';
    }

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        if (!isset(self::$_attributes[$this->_entity])) {
            if (!$this->_entity) {
                throw new \Exception('Undefined entity property value');
            }
            self::$_attributes[$this->_entity] = [];
            foreach (Ddm::getHelper('core')->getEntityAttributes($this->_entity) as $attribute) {
                self::$_attributes[$this->_entity][$attribute['attribute_id']] = new Attribute();
                self::$_attributes[$this->_entity][$attribute['attribute_id']]->addData($attribute)->setOrigData($attribute, null, true);
            }
        }
        return self::$_attributes[$this->_entity];
    }

    /**
     * @return Builder
     * @throws Exception
     */
    public function getAttributeSelect(): Builder
    {
        if ($this->_attributeSelect === null) {
            if (!$this->_entity) {
                throw new \Exception('Undefined entity property value');
            }
            $this->_attributeSelect = Db::table(['main_table' => 'attribute'])->where('main_table.entity_type', '=', $this->_entity);
        }
        return $this->_attributeSelect;
    }

    /**
     * @return array
     */
    public function getVisibleAttributes(): array
    {
        $attributes = [];
        foreach ($this->getAttributes() as $id => $attribute) {
            /* @var $attribute \Core\Model\Attribute */
            if ($attribute->is_visible) {
                $attributes[$id] = $attribute;
            }
        }
        return $attributes;
    }

    /**
     * @param int $languageId
     * @return $this
     */
    public function setLanguageId(int $languageId): static
    {
        $this->language_id = (int) $languageId;
        return $this;
    }

    /**
     * @param string $attributeCode Attribute Code
     * @return $this
     */
    public function addAttributeToSelect(string $attributeCode): static
    {
        if (isset($this->_selectedAttributes[$attributeCode])) {
            return $this;
        }
        $attribute = Ddm::getHelper('core')->getEntityAttribute($this->getEntity(), $attributeCode);
        if ($attribute && $attribute->backend_type != 'static') {
            $this->_selectedAttributes[$attributeCode] = ['attribute' => $attribute];
            $as = 'at_' . $attribute->attribute_code;
            $languageId = $attribute->is_global ? 0 : (int) $this->language_id;
            $primarykey = $this->getEntityPrimarykey();
            $builder = $this->getSelect();
            $builder->leftJoin(["{$as}_d" => $attribute->getTable(), true], function (JoinClause $join) use ($as, $attribute, $primarykey) {
                $join->on($as . '_d.entity_id', '=', 'main_table.' . $primarykey);
                $join->where($as . '_d.attribute_id', '=', (int) $attribute->attribute_id);
                $join->where($as . '_d.language_id', '=', 0);
            });
            if ($languageId) {
                $builder->leftJoin([$as => $attribute->getTable(), true], function (JoinClause $join) use ($as, $attribute, $primarykey, $languageId) {
                    $join->on($as . '.entity_id', '=', 'main_table.' . $primarykey);
                    $join->where($as . '.attribute_id', '=', (int) $attribute->attribute_id);
                    $join->where($as . '.language_id', '=', $languageId);
                });
                $this->_selectedAttributes[$attributeCode]['column'] = new Expression('IFNULL(' . $builder->wrap($as . '.value') . ',' . $builder->wrap($as . '_d.value') . ')' . ($attribute->getDecimalMultiple() === 1 ? '' : '/' . $attribute->getDecimalMultiple()));
                $builder->addSelect([$attribute->attribute_code => $this->_selectedAttributes[$attributeCode]['column']]);
            } else {
                $this->_selectedAttributes[$attributeCode]['column'] = $attribute->getDecimalMultiple() === 1 ? $as . '_d.value' : new Expression($builder->wrap($as . '_d.value') . '/' . $attribute->getDecimalMultiple());
                $builder->addSelect([$attribute->attribute_code => $this->_selectedAttributes[$attributeCode]['column']]);
            }
        }
        return $this;
    }

    /**
     * @param string $attributeCode
     * @param mixed $value
     * @param string $operator
     * @return $this
     */
    public function addAttributeToFilter(string $attributeCode, mixed $value, ?string $operator = null): static
    {
        $filter = $this->getAttributeFilter($attributeCode, $value);
        $this->getSelect()->where($filter['field'], $operator, $filter['value']);
        return $this;
    }

    /**
     * @param string $attributeCode
     * @param mixed $value
     * @return array
     */
    public function getAttributeFilter(string $attributeCode, mixed $value): array
    {
        if (isset($this->_selectedAttributes[$attributeCode])) {
            $attribute = $this->_selectedAttributes[$attributeCode]['attribute'];
            $fieldName = $this->_selectedAttributes[$attributeCode]['column'];
            if ($attribute->getDecimalMultiple() > 1) {
                $value = $this->_getAttributeDataValue($value, $attribute);
            }
            return ['field' => $fieldName, 'value' => $value];
        }
        $attribute = Ddm::getHelper('core')->getEntityAttribute($this->getEntity(), $attributeCode);
        if ($attribute && $attribute->backend_type != 'static') {
            return $this->addAttributeToSelect($attributeCode)->getAttributeFilter($attributeCode, $value);
        }
        return ['field' => "main_table.{$attributeCode}", 'value' => $value];
    }

    /**
     * @param string $attributeCode
     * @param int $languageId
     * @return mixed
     */
    public function getAttributeValue(string $attributeCode, ?int $languageId = null): mixed
    {
        if ($id = $this->getId()) {
            if (!isset($this->_attributesValue[$attributeCode]) && !array_key_exists($attributeCode, $this->_attributesValue)) {
                if ($this->{$attributeCode} !== null) {
                    $this->_attributesValue[$attributeCode] = $this->{$attributeCode};
                } elseif ($attribute = Ddm::getHelper('core')->getEntityAttribute($this->getEntity(), $attributeCode)) {
                    if ($attribute->backend_type == 'static') {
                        if ($this->issetData($attributeCode)) {
                            return $this->_attributesValue[$attributeCode] = $this->getData($attributeCode);
                        }
                        $data = Db::table($this->getResource()->getMainTableName())->where($this->getResource()->getPrimarykey(), '=', $id)->first();
                        if ($data) {
                            $this->addData($data);
                            $this->_attributesValue[$attributeCode] = isset($data[$attributeCode]) ? $data[$attributeCode] : null;
                        } else {
                            $this->_attributesValue[$attributeCode] = null;
                        }
                    } else {
                        if ($attribute->is_global) {
                            $languageId = 0;
                        } elseif ($languageId === null) {
                            $languageId = (int) $this->language_id;
                        }
                        $select = Db::table(['a' => $attribute->getTable(), true]);
                        if ($languageId) {
                            $select->leftJoin(['b' => $attribute->getTable(), true], function (JoinClause $join) use ($languageId) {
                                $join->on('b.entity_id', '=', 'a.entity_id');
                                $join->on('b.attribute_id', '=', 'a.attribute_id');
                                $join->where('b.language_id', '=', $languageId);
                            });
                            $select->addSelect(['default_value' => 'a.value', 'language_value' => 'b.value']);
                        } else {
                            $select->addSelect(['default_value' => 'a.value', 'language_value' => new Expression('NULL')]);
                        }
                        $data = $select->where('a.entity_id', $id)->where('a.attribute_id', $attribute->attribute_id)->where('a.language_id', '0')->first();
                        if ($data) {
                            if ($data['language_value'] === null) {
                                $this->_attributesValue[$attributeCode] = $attribute->getValue($data['default_value']);
                                $this->setUseDefaultAttribute($attributeCode, false);
                            } else {
                                $this->_attributesValue[$attributeCode] = $attribute->getValue($data['language_value']);
                            }
                        } else {
                            $this->_attributesValue[$attributeCode] = null;
                        }
                    }
                }
            }
            return $this->_attributesValue[$attributeCode];
        }
        return null;
    }

    /**
     * @param string $attributeCode
     * @return bool
     */
    public function isUseDefaultValue(string $attributeCode): bool
    {
        return isset($this->_useDefaultValue[$attributeCode]);
    }

    /**
     * @param string $attributeCode
     * @param bool $cover
     * @return $this
     */
    public function setUseDefaultAttribute(string $attributeCode, bool $cover = true): static
    {
        if (is_array($attributeCode)) {
            if ($cover) {
                $this->_useDefaultValue = [];
            }
            foreach ($attributeCode as $code) {
                $this->_useDefaultValue[$code] = true;
            }
        } else {
            $this->_useDefaultValue[$attributeCode] = true;
        }
        return $this;
    }

    /**
     * @param string|null|false $entity 如果为false, 则删除全部
     * @return $this
     */
    public function removeAttributesCache(string|null|false $entity = null): static
    {
        if ($entity === null) {
            $entity = $this->_entity;
        }
        if ($entity === false) {
            self::$_attributes = [];
            Ddm::getHelper('core')->removeAttributesCache(null);
        } else {
            if (isset(self::$_attributes[$entity])) {
                unset(self::$_attributes[$entity]);
            }
            Ddm::getHelper('core')->removeAttributesCache($entity);
        }
        return $this;
    }

    /**
     * 返回传递过来的值在数据库中的值是多少(例如小数和货币属性的值显示出来和在数据库中保存的值是不相同的)
     *
     * @param mixed $value
     * @param \Core\Model\Attribute $attribute
     * @return mixed
     */
    protected function _getAttributeDataValue(mixed $value, Attribute $attribute): mixed
    {
        if ($attribute->getDecimalMultiple() > 1) {
            if (is_array($value)) {
                foreach ($value as $k => $val) {
                    $value[$k] = $this->_getAttributeDataValue($val, $attribute);
                }
            } else {
                $value *= $attribute->getDecimalMultiple();
            }
        }
        return $value;
    }

    protected function _afterLoad(): static
    {
        if ($this->getId() && $this->getResource() instanceof Resource\Entity && $attributesValue = $this->getResource()->getAttributesValue($this)) {
            $data = [];
            $languageId = (int) $this->language_id;
            foreach ($this->getAttributes() as $attribute) {
                $attributeCode = $attribute->attribute_code;
                if ($languageId) {
                    if (isset($attributesValue[$languageId][$attributeCode])) {
                        $data[$attributeCode] = $attributesValue[$languageId][$attributeCode];
                    } elseif ($attribute->backend_type != 'static') {
                        $data[$attributeCode] = isset($attributesValue[0][$attributeCode]) ? $attributesValue[0][$attributeCode] : $this->getData($attributeCode);
                        $this->setUseDefaultAttribute($attributeCode, false);
                    }
                } elseif (isset($attributesValue[0][$attributeCode])) {
                    $data[$attributeCode] = $attributesValue[0][$attributeCode];
                }
            }
            $this->addData($data)->setOrigData(null, null, true);
        }
        return parent::_afterLoad();
    }

    protected function _beforeSave(): static
    {
        foreach ($this->_useDefaultValue as $attributeCode => $v) {
            $this->setData($attributeCode, false);
        }
        return parent::_beforeSave();
    }
}

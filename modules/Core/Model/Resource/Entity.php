<?php

namespace Core\Model\Resource;

use Core\Model\AbstractModel as ModelAbstractModel;
use Core\Model\Attribute;
use Core\Model\Entity as ModelEntity;
use Ddm\Db;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
abstract class Entity extends AbstractModel
{

    protected mixed $_attributeTables = null;

    /**
     * @param ModelAbstractModel $model
     * @return $this
     */
    protected function _afterSave(ModelAbstractModel $model): static
    {
        $this->_saveAttributes($model);
        return parent::_afterSave($model);
    }

    /**
     * @param ModelEntity $model
     * @return $this
     */
    protected function _saveAttributes(ModelEntity $model): static
    {
        if ($model->getId()) {
            foreach ($model->getAttributes() as $attribute) {
                if ($attribute->backend_type != 'static' && $model->getData($attribute->attribute_code) != $model->getOrigData($attribute->attribute_code)) {
                    $this->saveAttributeValue($model, $attribute);
                }
            }
        }
        return $this;
    }

    protected function _afterDelete(ModelAbstractModel $object): static
    {
        if ($id = (int) $object->getId()) {
            foreach ($this->getAttributeTables($object) as $table) {
                Db::table($table, true)->where('entity_id', '=', $id)->delete();
            }
        }
        return parent::_afterDelete($object);
    }

    /**
     * @param ModelEntity $model
     * @return array
     */
    public function getAttributeTables(ModelEntity $model): array
    {
        if ($this->_attributeTables === null) {
            $this->_attributeTables = [];
            if ($model instanceof ModelEntity) {
                foreach ($model->getAttributes() as $attribute) {
                    /** @var Attribute $attribute */
                    if ($attribute->backend_type != 'static' && $table = $attribute->getTable()) {
                        $this->_attributeTables[$table] = $table;
                    }
                }
            }
        }
        return $this->_attributeTables;
    }

    /**
     * @param ModelEntity $model
     * @param Attribute $attribute
     * @param int $languageId
     * @return $this
     */
    public function saveAttributeValue(ModelEntity $model, Attribute $attribute, ?int $languageId = null): static
    {
        $table = $attribute->getTable();
        $entityId = (int) $model->getId();
        $attributeCode = $attribute->attribute_code;
        $attributeId = (int) $attribute->attribute_id;
        if (!$attributeCode || !$attributeId) {
            return $this;
        }
        if ($model->{$attributeCode} === '' && $attribute->backend_type != 'varchar') {
            $model->{$attributeCode} = null;
        }
        $languageId = $languageId === null ? (int) $model->language_id : (int) $languageId;
        if ($languageId) {
            if ($model->{$attributeCode} === false || $model->{$attributeCode} === null) {
                if (!$attribute->is_global) {
                    Db::table($table, true)->where('entity_id', '=', $entityId)->where('attribute_id', '=', $attributeId)->where('language_id', '=', $languageId)->delete();
                } elseif ($model->{$attributeCode} === null) {
                    Db::getWriteConn()->insert($table, ['entity_id' => $entityId, 'attribute_id' => $attributeId, 'language_id' => 0, 'value' => null], false, ['value' => null]);
                }
            } else {
                Db::getWriteConn()->insert($table, ['entity_id' => $entityId, 'attribute_id' => $attributeId, 'language_id' => $attribute->is_global ? 0 : $languageId, 'value' => $attribute->setValue($model->{$attributeCode})], false, ['value' => $attribute->setValue($model->{$attributeCode})]);
            }
        } else {
            Db::getWriteConn()->insert($table, ['entity_id' => $entityId, 'attribute_id' => $attributeId, 'language_id' => 0, 'value' => $attribute->setValue($model->{$attributeCode})], false, ['value' => $attribute->setValue($model->{$attributeCode})]);
        }
        return $this;
    }

    /**
     * @param ModelEntity $model
     * @param int $languageId
     * @return array
     */
    public function getAttributesValue(ModelEntity $model, ?int $languageId = null): array
    {
        $sql = '';
        $languageId = $languageId === null ? (int) $model->language_id : (int) $languageId;
        $id = (int) $model->getId();
        $attributesValue = [];
        if ($attributes = $model->getAttributes()) {
            $quote = Db::getReadConn()->getIdentifierQuote();
            foreach ($this->getAttributeTables($model) as $table) {
                $table = $quote . str_replace($quote, $quote . $quote, $table) . $quote;
                $sql == '' or $sql .= ' UNION ALL ';
                $sql .= "SELECT b.attribute_id,b.attribute_code,b.backend_type,a.language_id,a.value FROM {$table} AS a " . "LEFT JOIN " . Db::getTable('attribute') . " AS b ON(b.attribute_id=a.attribute_id) " . "WHERE a.entity_id='{$id}' AND a.language_id" . ($languageId ? " IN(0,{$languageId})" : '=0') . " AND a.attribute_id IN(" . implode(',', array_keys($attributes)) . ")";
            }
            foreach (Db::getReadConn()->query($sql) as $row) {
                if ($row['attribute_id']) {
                    isset($attributesValue[$row['language_id']]) or $attributesValue[$row['language_id']] = [];
                    $attributesValue[$row['language_id']][$row['attribute_code']] = $attributes[$row['attribute_id']]->getValue($row['value'], $row['backend_type']);
                }
            }
        }
        return $attributesValue;
    }
}

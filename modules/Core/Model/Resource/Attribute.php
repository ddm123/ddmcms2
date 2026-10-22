<?php

namespace Core\Model\Resource;

use Core\Model\AbstractModel as ModelAbstractModel;
use Core\Model\Attribute as ModelAttribute;
use Ddm\Db;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Attribute extends AbstractModel
{

    protected mixed $_attributes = null;

    protected function _init(): static
    {
        $this->_setTable('attribute', 'attribute_id');
        return $this;
    }

    /**
     * @param string $entityTyp
     * @return $this
     */
    public function addEntityTypeToFilter(string $entityTyp): static
    {
        $this->getSelect()->where('main_table.entity_type', $entityTyp);
        return $this;
    }

    /**
     * @param string $o
     * @return $this
     */
    public function addOrder(?string $o = null): static
    {
        if ($o === null) {
            $this->getSelect()->orderBy('main_table.position', 'ASC');
        } elseif (preg_match('/^(.+?)(?:\s+(asc|desc))?$/i', $o, $matches)) {
            $this->getSelect()->orderBy($matches[1], isset($matches[2]) ? $matches[2] : 'asc');
        }
        return $this;
    }

    /**
     * @return array<int, ModelAttribute>
     */
    public function getAttributes(): array
    {
        if ($this->_attributes === null) {
            $this->_attributes = [];
            $select = Db::table(['main_table' => $this->getMainTableName()]);
            $select->select(['main_table.*']);
            $primarykey = $this->getPrimarykey();
            foreach ($this->getSelect()->get() as $_attribute) {
                $attributeId = $_attribute[$primarykey];
                $this->_attributes[$attributeId] = new ModelAttribute();
                $this->_attributes[$attributeId]->addData($_attribute);
                $this->_attributes[$attributeId]->setOrigData($_attribute, null, true);
            }
        }
        return $this->_attributes;
    }

    /**
     * @param array $attributes
     * @return $this
     */
    public function setAttributes(?array $attributes = null): static
    {
        $this->_attributes = $attributes;
        return $this;
    }
}

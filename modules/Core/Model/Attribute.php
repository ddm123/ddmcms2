<?php

namespace Core\Model;

use Ddm;
use Ddm\Db;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
/**
 * @method \Core\Model\Resource\Attribute getResource()
 */
class Attribute extends AbstractModel
{

    protected static mixed $_instance = null;

    protected static array $_decimalMultiples = [];

    protected mixed $_tableName = null;

    protected mixed $_decimalMultiple = null;

    /**
     * 使用单例模式
     * @return \Core\Model\Attribute
     */
    public static function singleton(): Attribute
    {
        return self::$_instance === null ? self::$_instance = new Attribute() : self::$_instance;
    }

    /**
     * @return string
     */
    public function getTable(): string
    {
        if ($this->_tableName === null) {
            $this->_tableName = false;
            if ($this->backend_type) {
                if ($this->backend_type == 'static') {
                    $this->_tableName = Db::getTable($this->entity_type);
                } elseif ($this->backend_table || $this->entity_type) {
                    $backendType = $this->backend_type != 'currency' && stripos($this->backend_type, 'decimal:') === false ? $this->backend_type : 'int';
                    $this->_tableName = Db::getTable(($this->backend_table ? $this->backend_table : $this->entity_type) . '_value_' . $backendType);
                }
            }
        }
        return $this->_tableName;
    }

    /**
     * @param mixed $value
     * @param string $backendType
     * @return mixed
     */
    public function getValue(mixed $value, ?string $backendType = null): mixed
    {
        $multiple = $backendType === null ? $this->getDecimalMultiple() : $this->getDecimalMultipleFromBackendType($backendType);
        if ($backendType === 1 && $backendType === null) {
            $backendType = $this->backend_type;
        }
        return $multiple === 1 ? $backendType == 'int' ? (int) $value : $value : $value / $multiple;
    }

    /**
     * @param mixed $value
     * @param string $backendType
     * @return mixed
     */
    public function setValue(mixed $value, ?string $backendType = null): mixed
    {
        $multiple = $backendType === null ? $this->getDecimalMultiple() : $this->getDecimalMultipleFromBackendType($backendType);
        if ($backendType === 1 && $backendType === null) {
            $backendType = $this->backend_type;
        }
        return $multiple === 1 ? $backendType == 'int' ? (int) $value : $value : round($value * $multiple, 0);
    }

    /**
     * @return int
     */
    public function getDecimalMultiple(): int
    {
        if ($this->_decimalMultiple === null) {
            $this->_decimalMultiple = $this->getDecimalMultipleFromBackendType((string) $this->backend_type);
        }
        return $this->_decimalMultiple;
    }

    /**
     * @param string $backendType
     * @return int
     */
    public function getDecimalMultipleFromBackendType(string $backendType): int
    {
        if (!isset(self::$_decimalMultiples[$backendType])) {
            self::$_decimalMultiples[$backendType] = 1;
            if (!$backendType) {
                return self::$_decimalMultiples[$backendType];
            }
            $decimals = 0;
            if (stripos($backendType, 'decimal:') === 0) {
                $decimals = substr($backendType, 8) or $decimals = 2;
            } elseif ($backendType == 'currency') {
                $decimals = (int) Ddm::getConfig()->getConfigValue('system/currency/precision') or $decimals = 2;
            }
            if ($decimals) {
                self::$_decimalMultiples[$backendType] = (int) str_pad('1', $decimals + 1, '0', STR_PAD_RIGHT);
            }
        }
        return self::$_decimalMultiples[$backendType];
    }

    protected function _afterSave(): static
    {
        Ddm::getHelper('core')->removeAttributesCache($this->entity_type);
        return parent::_afterSave();
    }

    protected function _beforeDelete(): static
    {
        if ($this->is_system == 1 || $this->backend_type == 'static') {
            throw new \Exception('System attributes can not be deleted');
        }
        Ddm::dispatchEvent('attribute_delete_before', ['object' => $this]);
        return parent::_beforeDelete();
    }

    protected function _afterDelete(): static
    {
        Ddm::getHelper('core')->removeAttributesCache($this->entity_type);
        Ddm::dispatchEvent('attribute_delete_after', ['object' => $this]);
        return parent::_afterDelete();
    }

    protected function _beforeSave(): static
    {
        if (empty($this->entity_type)) {
            throw new \Exception('Undefined \'entity_type\' property value');
        }
        if (empty($this->attribute_code)) {
            throw new \Exception('Undefined \'attribute_code\' property value');
        }
        if (empty($this->backend_type)) {
            throw new \Exception('Undefined \'backend_type\' property value');
        }
        return parent::_beforeSave();
    }
}

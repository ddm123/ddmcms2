<?php

namespace Ddm;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class DataObject
{

    protected array $_data = [];

    protected array $_origData = [];

    protected array $_readOnlyAttribute = [];

    public function __construct()
    {
        if (($args = func_get_args()) && is_array($args[0])) {
            if (isset($args[1]) && $args[1] === false) {
                $this->addData($args[0]);
            } else {
                $this->parseData($args[0], isset($args[1]) && is_object($args[1]) ? $args[1] : $this);
            }
        }
    }

    public function __get(mixed $name): mixed
    {
        if (property_exists($this, $name)) {
            throw new \Exception('Cannot access protected/private property ' . get_class($this) . '::$' . $name);
        }
        return $this->getData($name);
    }

    public function __isset(string $name): bool
    {
        return $this->issetData($name);
    }

    public function __set(mixed $name, mixed $value): void
    {
        if (property_exists($this, $name)) {
            throw new \Exception('Cannot access protected/private property ' . get_class($this) . '::$' . $name);
        } elseif (isset($this->_readOnlyAttribute[$name])) {
            throw new \Exception("The \"{$name}\" attribute is read-only");
        } else {
            //$this->$name = $value;
            $this->setData($name, $value);
        }
    }

    public function __unset(string $name): void
    {
        if (property_exists($this, $name)) {
            throw new \Exception('Cannot access protected/private property ' . get_class($this) . '::$' . $name);
        } else {
            $this->unsetData($name);
        }
    }

    /**
     * 设置哪些属性是只读，所以你必须先给这些属性赋值后才调用该方法来指定为只读
     * @param string|array $property
     * @param bool $unset 是否取消只读状态
     * @return $this
     */
    public function setReadOnlyProperty(string|array $property, bool $unset = false): static
    {
        if ($unset) {
            if (is_array($property)) {
                foreach ($property as $value) {
                    unset($this->_readOnlyAttribute[$value]);
                }
            } else {
                unset($this->_readOnlyAttribute[$property]);
            }
        } elseif (is_array($property)) {
            foreach ($property as $value) {
                $this->_readOnlyAttribute[$value] = true;
            }
        } else {
            $this->_readOnlyAttribute[$property] = true;
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getReadOnlyProperty(): array
    {
        return array_keys($this->_readOnlyAttribute, true);
    }

    /**
     * 如果$name为一个数组,则递归设置每个数组元素都成为一个对象
     * @param string|array $name
     * @param mixed $value
     * @return $this
     */
    public function setData(string|array $name, mixed $value = null): static
    {
        if ($name) {
            if (is_array($name)) {
                $this->parseData($name);
            } elseif (isset($this->_readOnlyAttribute[$name])) {
                throw new \Exception("The \"{$name}\" attribute is read-only");
            } else {
                $this->_data[$name] = $value;
                //$this->$name = $value;
            }
        }
        return $this;
    }

    /**
     * 该方法和setData()方法的区别仅仅是不递归设置每个数组元素都成为一个对象,其它都是一样的
     * @param string|array $name
     * @param mixed $value
     * @return $this
     */
    public function addData(string|array $name, mixed $value = null): static
    {
        if ($name) {
            if (is_array($name)) {
                foreach ($name as $key => $val) {
                    $this->setData($key, $val);
                }
            } else {
                $this->setData($name, $value);
            }
        }
        return $this;
    }

    /**
     * @param string|null $name
     * @return $this
     */
    public function unsetData(?string $name = null): static
    {
        if ($name === null) {
            foreach ($this->_data as $key => $value) {
                $this->unsetData($key);
            }
        } elseif (isset($this->_readOnlyAttribute[$name])) {
            throw new \Exception("The \"{$name}\" attribute is read-only");
        } else {
            unset($this->_data[$name]);
        }
        return $this;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function issetData(string $name): bool
    {
        return isset($this->_data[$name]) || array_key_exists($name, $this->_data);
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getData(?string $name = null): mixed
    {
        if ($name === null) {
            return $this->toArray();
        }
        return isset($this->_data[$name]) ? $this->_data[$name] : null;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getOrigData(?string $name = null): mixed
    {
        if ($name === null) {
            return $this->_origData;
        }
        return isset($this->_origData[$name]) ? $this->_origData[$name] : null;
    }

    /**
     * @param string|null $name
     * @param mixed $value
     * @param bool $cover 如果已经被赋值过了是否重新赋值
     * @return $this
     */
    public function setOrigData(?string $name = null, mixed $value = null, bool $cover = false): static
    {
        if ($name === null || is_array($name)) {
            $name === null and $name = $this->toArray();
            if ($cover) {
                $this->_origData = $name;
            } else {
                foreach ($name as $key => $val) {
                    if (!isset($this->_origData[$key])) {
                        $this->setOrigData($key, $val, $cover);
                    }
                }
            }
        } elseif ($value === null) {
            unset($this->_origData[$name]);
        } elseif ($cover || !isset($this->_origData[$name])) {
            $this->_origData[$name] = $value;
        }
        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        foreach ($this->_data as $name => $value) {
            //$value===$this->$name or $this->_data[$name] = $this->$name;
            if ($value instanceof DataObject) {
                $this->_data[$name] = $value->toArray();
            }
        }
        return $this->_data;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return print_r($this->toArray(), true);
    }

    /**
     * 递归把所有值都转为$object对象
     * @param array $data
     * @param DataObject $object
     * @return DataObject
     * @throws Exception
     */
    final public function parseData(array $data, ?DataObject $object = null): DataObject
    {
        $result = $object === null ? $this : $object;
        if ($data) {
            $result->_data = array_merge($result->_data, $data);
            foreach ($data as $key => $value) {
                $result->{$key} = is_array($value) ? $this->parseData($value, new DataObject()) : $value;
            }
        }
        return $result;
    }
}

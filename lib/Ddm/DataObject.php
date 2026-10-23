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

    public function __construct(?array $data = null)
    {
        if ($data) {
            $this->_data = $data;
        }
    }

    public function __get(string $name): mixed
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

    public function __set(string $name, mixed $value): void
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
     *
     * @param string|string[] $property
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
     * @param array $data
     * @param bool $clearData 是否清空原数据，默认为true
     * @return static
     */
    public function setAllData(array $data, bool $clearData = true): static
    {
        if ($clearData) {
            $this->_data = [];
        }

        foreach ($data as $key => $val) {
            $this->setData($key, $val);
        }
        return $this;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function setData(string $name, mixed $value): static
    {
        if (isset($this->_readOnlyAttribute[$name])) {
            throw new \Exception("The \"{$name}\" attribute is read-only");
        }
        $this->_data[$name] = $value;
        return $this;
    }

    /**
     * @param string|array $name
     * @param mixed $value
     * @return $this
     */
    public function addData(string|array $name, mixed $value = null): static
    {
        if ($name) {
            if (is_array($name)) {
                $this->setAllData($name, false);
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
        } else if (isset($this->_readOnlyAttribute[$name])) {
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
    public function getData(string ...$name): mixed
    {
        if (isset($name[1])) {
            $data = [];
            foreach ($name as $key) {
                $data[$key] = $this->_data[$name] ?? null;
            }
            return $data;
        }
        if ($name) {
            return $this->_data[$name[0]] ?? null;
        }
        return $this->_data;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getOrigData(string ...$name): mixed
    {
        if (isset($name[1])) {
            $data = [];
            foreach ($name as $key) {
                $data[$key] = $this->_origData[$name] ?? null;
            }
            return $data;
        }
        if ($name) {
            return $this->_origData[$name[0]] ?? null;
        }
        return $this->_origData;
    }

    /**
     * @param string|null|array $name
     * @param mixed $value
     * @param bool $clearOrigData 是否清空原数据，默认为false（只有第1个参数$name为数组时才有效）
     * @return $this
     */
    public function setOrigData(string|null|array $name = null, mixed $value = null, bool $clearOrigData = false): static
    {
        if ($name === null || is_array($name)) {
            $name ??= $this->_data;
            if ($clearOrigData) {
                $this->_origData = $name;
            } else {
                $this->_origData = array_replace($this->_origData, $name);
            }
        } elseif ($value === null) {
            unset($this->_origData[$name]);
        } else {
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
     * 判断两个变量是否相等
     */
    public function isEqual(mixed $a, mixed $b): bool
    {
        if ($a === $b) return true;
        if ($a === null || $b === null) return $a === $b;
        if ($a === '' || $b === '') return $a === $b;
        if (is_bool($a) || is_bool($b)) return $a === $b;

        $aIsArray = is_array($a);
        $bIsArray = is_array($b);
        if ($aIsArray || $bIsArray) {
            if (!$aIsArray || !$bIsArray || count($a) !== count($b)) {
                return false;
            }
            foreach ($a as $k => $v) {
                if (!$this->isEqual($v, $b[$k] ?? null)) {
                    return false;
                }
            }
            return true;
        }

        return $a == $b;
    }

    /**
     * 获取已变更的数据
     */
    public function getChangedData(): array
    {
        $changedData = [];
        foreach ($this->_data as $name => $value) {
            if (!$this->isEqual($value, $this->_origData[$name] ?? null)) {
                $changedData[$name] = $value;
            }
        }
        return $changedData;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return print_r($this->toArray(), true);
    }
}

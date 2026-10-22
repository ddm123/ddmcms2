<?php

namespace Core\Model\Attribute\Source;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
abstract class AbstractModel
{

    protected mixed $_options = null;

    /**
     * @param bool $withEmpty
     * @param bool $defaultValue
     * @return array
     */
    abstract public function getAllOptions(bool $withEmpty = false, bool $defaultValue = true): array;

    /**
     * @param bool $withEmpty
     * @param bool $defaultValue
     * @return array
     */
    public function getBaseOptions(bool $withEmpty = false, bool $defaultValue = true): array
    {
        $options = [];
        foreach ($this->getAllOptions($withEmpty, $defaultValue) as $option) {
            $options[$option['value']] = $option['label'];
        }
        return $options;
    }

    /**
     * @param bool $withEmpty
     * @param bool $defaultValue
     * @return string
     */
    public function toComboboxData(bool $withEmpty = false, bool $defaultValue = false): string
    {
        $str = '[';
        foreach ($this->getAllOptions($withEmpty, $defaultValue) as $option) {
            $options[$option['value']] = $option['label'];
            $str == '[' or $str .= ',';
            $str .= '["' . str_replace('"', '\"', $option['value']) . '","' . str_replace('"', '\"', $option['label']) . '"]';
        }
        $str .= ']';
        return $str;
    }
}

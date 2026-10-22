<?php

namespace Core\Model\Attribute\Source;

use Ddm;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Yesno extends AbstractModel
{

    protected static mixed $_instance = null;

    protected mixed $_defaultOptions = null;

    /**
     * 使用单例模式
     * @return \Core\Model\Attribute\Source\Yesno
     */
    public static function singleton(): Yesno
    {
        return self::$_instance === null ? self::$_instance = new Yesno() : self::$_instance;
    }

    /**
     * @param bool $withEmpty
     * @return array
     */
    public function getDefaultOptions(bool $withEmpty = false): array
    {
        if ($this->_defaultOptions === null) {
            $this->_defaultOptions = [['value' => '1', 'label' => '是'], ['value' => '0', 'label' => '否']];
        }
        if ($withEmpty) {
            return array_merge([0 => ['value' => '', 'label' => '']], $this->_defaultOptions);
        }
        return $this->_defaultOptions;
    }

    /**
     * @param bool $withEmpty
     * @param bool $defaultValue
     * @return array
     */
    public function getAllOptions(bool $withEmpty = false, bool $defaultValue = true): array
    {
        if ($defaultValue) {
            return $this->getDefaultOptions($withEmpty);
        }
        if ($this->_options === null) {
            $this->_options = [];
            foreach ($this->getDefaultOptions(false) as $option) {
                $this->_options[] = ['value' => $option['value'], 'label' => Ddm::getTranslate('core')->translate($option['label'])];
            }
        }
        if ($withEmpty) {
            return array_merge([0 => ['value' => '', 'label' => '']], $this->_options);
        }
        return $this->_options;
    }
}

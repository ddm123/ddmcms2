<?php

namespace News\Model\Category;

use Core\Model\Attribute\Source\AbstractModel;
use Ddm;
use News\Model\Category;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Option extends AbstractModel
{

    protected static mixed $_instance = null;

    protected mixed $_options = [];

    protected array $_optionsFromLanguage = [];

    /**
     * 使用单例模式
     * @return \News\Model\Category\Option
     */
    public static function singleton(): Option
    {
        return self::$_instance === null ? self::$_instance = new Option() : self::$_instance;
    }

    /**
     * @param bool $withEmpty
     * @param bool $defaultValue
     * @return array
     */
    public function getAllOptions(bool $withEmpty = false, bool $defaultValue = true): array
    {
        return $this->getAllOptionsFromLanguage($withEmpty, $defaultValue ? 0 : null);
    }

    /**
     * @param bool $withEmpty
     * @param int $languageId
     * @return array
     */
    public function getAllOptionsFromLanguage(bool $withEmpty = false, ?int $languageId = null): array
    {
        $languageId = $languageId === null ? Ddm::getLanguage()->language_id : (int) $languageId;
        if (!isset($this->_optionsFromLanguage[$languageId])) {
            $this->_optionsFromLanguage[$languageId] = [];
            $category = new Category();
            $category->setLanguageId($languageId)->getSelect()->select(['main_table.category_id', 'main_table.position'])->orderBy("main_table.position", "ASC");
            $category->addAttributeToSelect('name');
            foreach ($category->getSelect()->get() as $item) {
                $this->_optionsFromLanguage[$languageId][] = ['value' => $item['category_id'], 'label' => $item['name']];
            }
        }
        if ($withEmpty) {
            return array_merge([0 => ['value' => '', 'label' => '']], $this->_optionsFromLanguage[$languageId]);
        }
        return $this->_optionsFromLanguage[$languageId];
    }
}

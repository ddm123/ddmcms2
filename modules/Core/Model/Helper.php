<?php

namespace Core\Model;

use Ddm;
use Ddm\Cache;
use Ddm\Db;
use Ddm\StringHelper;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Helper
{

    public const ENTITY_ATTRIBUTE_CACHE_KEY = 'entity_attributes_';

    public const CHARS_LOWERS = 'abcdefghijklmnopqrstuvwxyz';

    public const CHARS_UPPERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    public const CHARS_DIGITS = '0123456789';

    public const CHARS_SPECIALS = '!$*+-.=?@^_|~';

    private mixed $_formKey = null;

    protected mixed $_dateTime = null;

    protected array $_attributes = [];

    protected array $_entityAttributes = [];

    public function __construct()
    {
        //;
    }

    /**
     * @return string
     */
    public function getFormKey(): string
    {
        if ($this->_formKey === null) {
            $this->_formKey = Ddm::getSession()->form_key or Ddm::getSession()->setData('form_key', $this->_formKey = $this->getRandomString(8));
        }
        return $this->_formKey;
    }

    /**
     * @return $this
     */
    public function destroyFormKey(): static
    {
        Ddm::getSession()->unsetData('form_key');
        return $this;
    }

    /**
     * @return string
     */
    public function getRandomString(mixed $len, mixed $chars = null): string
    {
        $chars or $chars = self::CHARS_LOWERS . self::CHARS_UPPERS . self::CHARS_DIGITS;
        mt_srand(10000000 * (float) microtime());
        for ($i = 0, $str = '', $lc = strlen($chars) - 1; $i < $len; $i++) {
            $str .= $chars[mt_rand(0, $lc)];
        }
        return $str;
    }

    /**
     * 返回一个带单位的货币显示格式
     * @param float $number
     * @return string
     */
    public function formatCurrency(float $number): string
    {
        return StringHelper::singleton()->currency($number);
    }

    /**
     * 把字节以一个友好的显示格式返回
     * @param int $bytes
     * @return string
     */
    public function formatBytes(int $bytes): string
    {
        return StringHelper::singleton()->formatBytes($bytes);
    }

    /**
     * @param string $unixtimestamp
     * @param string $format 和date()函数的第一参数是一样的
     * @return string
     */
    public function formatDate(string $unixtimestamp, ?string $format = null): string
    {
        return $this->getDateTime()->setTimestamp($unixtimestamp)->date($format);
    }

    /**
     * @param string $unixtimestamp
     * @param string $format 和date()函数的第一参数是一样的
     * @return string
     */
    public function formatDateTime(string $unixtimestamp, ?string $format = null): string
    {
        return $this->getDateTime()->setTimestamp($unixtimestamp)->dateTime($format);
    }

    /**
     * @return \Core\Model\DateTime
     */
    public function getDateTime(): DateTime
    {
        return $this->_dateTime === null ? $this->_dateTime = new DateTime() : $this->_dateTime;
    }

    /**
     * 出于安全考虑, 保存一个文件时请使用该方法, 不要直接使用file_put_contents()函数
     * @param string $filename
     * @param mixed $data
     * @param int $flags
     * @param int $mode
     * @return int
     */
    public function saveFile(string $filename, mixed $data, int $flags = LOCK_EX, int $mode = 0755): int
    {
        $path = dirname($filename);
        if ($path && $path != '.') {
            if (strpos(strtr($filename, '\\', '/'), SITE_ROOT . '/data/') === 0) {
                $res = @file_put_contents($filename, $data, $flags) or mkdir($path, $mode, true) and $res = file_put_contents($filename, $data, $flags);
                if ($res !== false) {
                    return $res;
                }
            }
        }
        throw new \Exception('This folder(' . $path . ') prohibit write!');
    }

    /**
     * 删除一个非空文件夹
     * @param string $path
     * @return \Core\Model\Helper
     */
    public function deleteFolder(string $path): Helper
    {
        if ($handle = opendir($path)) {
            while (false !== $file = readdir($handle)) {
                if ($file != '.' && $file != '..') {
                    if (is_dir("{$path}/{$file}")) {
                        $this->deleteFolder("{$path}/{$file}");
                    } else {
                        unlink("{$path}/{$file}");
                    }
                }
            }
            closedir($handle);
            rmdir($path);
        }
        return $this;
    }

    /**
     * 获取一个实体的属性模型
     * @param string $entityType
     * @param string $attributeCode
     * @return \Core\Model\Attribute
     */
    public function getEntityAttribute(string $entityType, string $attributeCode): Attribute
    {
        if (!isset($this->_attributes["{$entityType}/{$attributeCode}"])) {
            $this->_attributes["{$entityType}/{$attributeCode}"] = false;
            $data = $this->getEntityAttributes($entityType);
            if (isset($data[$attributeCode])) {
                $this->_attributes["{$entityType}/{$attributeCode}"] = new Attribute();
                $this->_attributes["{$entityType}/{$attributeCode}"]->addData($data[$attributeCode])->setOrigData($data[$attributeCode], null, true);
            }
        }
        return $this->_attributes["{$entityType}/{$attributeCode}"];
    }

    /**
     * 获取一个实体的全部属性
     * @param string $entityType
     * @return array
     */
    public function getEntityAttributes(string $entityType): array
    {
        if (!isset($this->_entityAttributes[$entityType])) {
            $this->_entityAttributes[$entityType] = Cache::load(self::ENTITY_ATTRIBUTE_CACHE_KEY . $entityType);
            if ($this->_entityAttributes[$entityType] === false) {
                $this->_entityAttributes[$entityType] = Db::table('attribute')->where('entity_type', '=', $entityType)->orderBy('position', 'ASC')->get(null, 'attribute_code');
                Cache::save(self::ENTITY_ATTRIBUTE_CACHE_KEY . $entityType, $this->_entityAttributes[$entityType], ['attribute'], 0);
            }
        }
        return $this->_entityAttributes[$entityType];
    }

    /**
     * 删除一个实体属性的缓存
     * @param string|null $entityType
     * @return $this
     */
    public function removeAttributesCache(?string $entityType = null): static
    {
        if ($entityType === null) {
            foreach ($this->_entityAttributes as $entityType => $entityAttribute) {
                Cache::remove(self::ENTITY_ATTRIBUTE_CACHE_KEY . $entityType);
            }
            $this->_entityAttributes = [];
        } else {
            if (isset($this->_entityAttributes[$entityType])) {
                unset($this->_entityAttributes[$entityType]);
            }
            Cache::remove(self::ENTITY_ATTRIBUTE_CACHE_KEY . $entityType);
        }
        $this->_attributes = [];
        return $this;
    }
}

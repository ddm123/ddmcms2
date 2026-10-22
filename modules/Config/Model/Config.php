<?php

namespace Config\Model;

use Core\Model\AbstractModel;
use Ddm;
use Ddm\Cache;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
/**
 * @method \Config\Model\Resource\Config getResource
 */
class Config extends AbstractModel
{

    public const XML_CONFIG_FILE = '/data/configs/define.xml.php';

    protected mixed $_xmlConfig = null;

    protected mixed $_dbConfig = null;

    protected array $_configs = [];

    private mixed $_xmlConfigFile = null;

    private string $_configCacheFile = '';

    private string $_configCachePath = '';

    public function __construct()
    {
        parent::__construct();
        $this->_xmlConfigFile = SITE_ROOT . self::XML_CONFIG_FILE;
        $this->_configCachePath = SITE_ROOT . '/data/cache/configs';
        //is_dir($this->_configCachePath) or mkdir($this->_configCachePath, 0700, true);
        $this->_configCacheFile = $this->_configCachePath . '/configs%20cache+data.php';
    }

    public function __destruct()
    {
        unset($this->_xmlConfig);
    }

    /**
     * @param string $path
     * @param int $languageId
     * @return string|null
     */
    public function getConfigValue(string $path, ?int $languageId = null): ?string
    {
        if ($languageId === null) {
            $languageId = Ddm::getLanguage()->language_id;
        }
        if (!isset($this->_configs[$languageId])) {
            $this->_configs[$languageId] = $this->_getConfigsFromLanguageId($languageId);
        }
        return isset($this->_configs[$languageId][$path]) ? $this->_configs[$languageId][$path] : null;
    }

    /**
     * @param string $path
     * @return $this
     */
    public function loadFromPath(string $path): static
    {
        $this->getResource()->loadFromPath($this, $path);
        return $this;
    }

    /**
     * 从数据库中删除记录
     * @param string $path
     * @return $this
     */
    public function removeConfigValue(string $path): static
    {
        $this->getResource()->removeConfigValue($path);
        return $this;
    }

    /**
     * 将多维数组转换为一维,键名会合并为一维的键名,用斜杠分隔
     * @param array $arr 需要转换的数组
     * @param null $key 调用时不要传递该参数
     * @return array
     */
    public function arrayToOne(array $arr, ?string $key = null): array
    {
        $_array = [];
        if ($key !== null) {
            $key .= '/';
        }
        foreach ($arr as $k => $value) {
            $a = is_array($value) ? $this->arrayToOne($value, "{$key}{$k}") : ["{$key}{$k}" => $value];
            foreach ($a as $_k => $_v) {
                if (isset($_array[$_k])) {
                    if (!is_array($_array[$_k]) || key($_array[$_k]) !== 0) {
                        $_array[$_k] = [$_array[$_k]];
                    }
                    $_array[$_k][] = $_v;
                } else {
                    $_array[$_k] = $_v;
                }
            }
        }
        return $_array;
    }

    /**
     * @param SimpleXMLElement $xml
     * @return array
     */
    public function xmlToArray(\SimpleXMLElement $xml): array
    {
        $_array = [];
        foreach ($xml as $key => $value) {
            $isSimpleXMLElement = $value instanceof \SimpleXMLElement;
            if ($isSimpleXMLElement && $attributes = $value->attributes()) {
                isset($_array["{$key}@attributes"]) or $_array["{$key}@attributes"] = [];
                foreach ($attributes as $aName => $aValue) {
                    if (isset($_array["{$key}@attributes"][$aName])) {
                        if (!is_array($_array["{$key}@attributes"][$aName]) || !isset($_array["{$key}@attributes"][$aName][0])) {
                            $_array["{$key}@attributes"][$aName] = [$_array["{$key}@attributes"][$aName]];
                        }
                        $_array["{$key}@attributes"][$aName][] = $this->_transformXmlValue((string) $aValue);
                    } else {
                        $_array["{$key}@attributes"][$aName] = $this->_transformXmlValue((string) $aValue);
                    }
                }
            }
            $_value = $isSimpleXMLElement && count($value->children()) ? $this->xmlToArray($value) : (string) $value;
            if (isset($_array[$key])) {
                if (!is_array($_array[$key]) || !isset($_array[$key][0])) {
                    $_array[$key] = [$_array[$key]];
                }
                $_array[$key][] = $this->_transformXmlValue($_value);
            } else {
                $_array[$key] = $this->_transformXmlValue($_value);
            }
        }
        return $_array;
    }

    /**
     * @param string $xml_file
     * @return array
     */
    public function xmlFileToArray(string $xml_file): array
    {
        $xml = simplexml_load_file($xml_file, 'SimpleXMLElement', LIBXML_NOCDATA);
        return $xml ? $this->xmlToArray($xml) : null;
    }

    /**
     * @return array
     */
    public function getXmlConfig(): array
    {
        if ($this->_xmlConfig === null) {
            if (is_file($this->_configCacheFile)) {
                $data = str_replace('<?php exit;?>', '', file_get_contents($this->_configCacheFile));
                $this->_xmlConfig = unserialize($data);
                if ($this->_xmlConfig === null) {
                    $this->_xmlConfig = [];
                }
            } elseif (Ddm::isInstalled()) {
                $xml = '<?xml version="1.0" encoding="utf-8"?>';
                $xml .= preg_replace(['/<\!\-\-.*?\-\->/s', '/<\?.*?\?>/is'], ['', ''], file_get_contents($this->_xmlConfigFile));
                $this->_xmlConfig = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
                $this->_xmlConfig = $this->xmlToArray($this->_xmlConfig);
                Ddm::getHelper('core')->saveFile($this->_configCacheFile, '<?php exit;?>' . serialize($this->_xmlConfig), LOCK_EX);
            } else {
                $this->_xmlConfig = [];
            }
        }
        return $this->_xmlConfig;
    }

    /**
     * @return string
     */
    public function getXmlConfigFile(): string
    {
        return $this->_xmlConfigFile;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function getXmlValue(mixed $value): mixed
    {
        return $this->_transformXmlValue($value);
    }

    /**
     * 删除配置缓存
     * @return $this
     */
    public function clearCache(): static
    {
        if (is_file($this->_configCacheFile)) {
            unlink($this->_configCacheFile);
        }
        foreach (glob($this->_configCachePath . '/configs++#*.php') as $file) {
            unlink($file);
        }
        foreach (Ddm::getModuleConfig() as $_moduleName => $_value) {
            Cache::remove(Ddm::MODULE_CONFIG_CACHE_KEY_PRE . $_moduleName);
        }
        Cache::remove(Ddm::MODULES_CONFIG_CACHE_KEY);
        $this->_dbConfig = null;
        $this->_xmlConfig = null;
        $this->_configs = [];
        return $this;
    }

    /**
     * @return array
     */
    public function getDbConfig(): array
    {
        if ($this->_dbConfig === null) {
            $this->_dbConfig = [];
            $config = $this->getXmlConfig();
            if (isset($config['db']['driver'])) {
                $this->_dbConfig = $config['db'];
                $this->_dbConfig['driver'] = $config['db']['driver'];
                $this->_dbConfig['use_pconnect'] = isset($config['db']['use_pconnect']) ? (bool) $config['db']['use_pconnect'] : false;
                isset($this->_dbConfig['port']) or $this->_dbConfig['port'] = '3306';
                isset($this->_dbConfig['character']) or $this->_dbConfig['character'] = null;
            } elseif (isset($config['db'])) {
                foreach ($config['db'] as $key => $value) {
                    $this->_dbConfig[$key] = $value;
                    $this->_dbConfig[$key]['driver'] = $value['driver'];
                    $this->_dbConfig[$key]['use_pconnect'] = isset($value['use_pconnect']) ? (bool) $value['use_pconnect'] : false;
                    isset($this->_dbConfig[$key]['port']) or $this->_dbConfig[$key]['port'] = '3306';
                    isset($this->_dbConfig[$key]['character']) or $this->_dbConfig[$key]['character'] = null;
                }
            }
            $this->_dbConfig['tablepre'] = isset($config['db@attributes']['tablepre']) ? $config['db@attributes']['tablepre'] : '';
        }
        return $this->_dbConfig;
    }

    /**
     * @param int $languageId
     * @return array
     */
    protected function _getConfigsFromLanguageId(int $languageId): array
    {
        $data = [];
        if (Ddm::isInstalled()) {
            $languageId = (int) $languageId;
            $cacheFile = $this->_configCachePath . "/configs++#{$languageId}.php";
            if (is_file($cacheFile)) {
                $data = unserialize(str_replace('<?php exit;?>', '', file_get_contents($cacheFile)));
            } else {
                $data = array_merge($this->arrayToOne($this->getXmlConfig()), $this->getResource()->getConfigs($languageId));
                Ddm::getHelper('core')->saveFile($cacheFile, '<?php exit;?>' . serialize($data), LOCK_EX);
            }
        }
        return $data;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    protected function _transformXmlValue(mixed $value): mixed
    {
        switch ($value) {
            case 'true':
            case 'TRUE':
                $_value = true;
                break;
            case 'false':
            case 'FALSE':
                $_value = false;
                break;
            case 'null':
            case 'NULL':
                $_value = null;
                break;
            default:
                $_value = $value;
        }
        return $_value;
    }

    protected function _afterLoad(): static
    {
        parent::_afterLoad();
        if ($id = $this->getId()) {
            if ($configs = $this->getResource()->getConfigsFromConfigId($id)) {
                $this->addData('values', $configs);
            }
        }
        return $this;
    }
}

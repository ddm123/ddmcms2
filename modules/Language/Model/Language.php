<?php

namespace Language\Model;

use Core\Model\AbstractModel;
use Ddm;
use Ddm\Cache;
use Ddm\Controller;
use Ddm\DataObject;
use Ddm\Request;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
/**
 * @property-read int $language_id
 * @property-read string $language_code
 * @property-read string $language_name
 * @method \Language\Model\Resource\Language getResource()
 */
class Language extends AbstractModel
{

    public const LANGUAGE_URL_VARNAME = '__lan';

    public const LANGUAGE_COOKIE_VARNAME = 'ddmcms_l_id';

    public const ALL_LANGUAGE_CACHE_KEY = 'all_language';

    protected static mixed $_allLanguage = null;

    protected mixed $_loadLanguageId = -1;

    protected mixed $_allBaseUrl = null;

    protected array $_baseUrlCache = [];

    protected bool $_isSaveToCookie = false;

    protected mixed $_defaultLanguage = null;

    protected mixed $_urlPathInfo = null;

    /**
     * @param bool $asObject
     * @return array|DataObject
     */
    public function getDefaultLanguage(bool $asObject = false): array|DataObject
    {
        if ($this->_defaultLanguage === null) {
            $languageCode = Ddm::getConfig()->getConfigValue('web/language/default_code', 0) or $languageCode = 'zh_CN';
            $languageName = Ddm::getConfig()->getConfigValue('web/language/default_name', 0) or $languageName = '简体中文';
            $this->_defaultLanguage = ['language_id' => '0', 'language_code' => $languageCode, 'language_name' => $languageName, 'position' => '0'];
        }
        return $asObject ? new DataObject($this->_defaultLanguage) : $this->_defaultLanguage;
    }

    /**
     * 返回所有已启用的语言
     * @param bool $includeDefault
     * @return array
     */
    public function getAllLanguage(bool $includeDefault = false): array
    {
        if (self::$_allLanguage === null) {
            self::$_allLanguage = Cache::load(self::ALL_LANGUAGE_CACHE_KEY);
            if (self::$_allLanguage === false) {
                self::$_allLanguage = $allLanguage = $this->getResource()->getAllLanguage(true);
                Cache::save(self::ALL_LANGUAGE_CACHE_KEY, $allLanguage, ['language'], 0);
                //永久保存
            }
        }
        if ($includeDefault) {
            $defaultLanguage = $this->getDefaultLanguage(false);
            return array_merge([$defaultLanguage['language_code'] => $defaultLanguage], self::$_allLanguage);
        }
        return self::$_allLanguage;
    }

    /**
     * @param int|string|false $languageId 语言ID或语言Code
     * @return $this
     */
    public function loadFromVisitor(int|string|false $languageId = false): static
    {
        if ($this->_loadLanguageId != $languageId) {
            $this->_loadLanguageId = $languageId;
            Controller::singleton()->getUriPath();
            if ((string) $languageId !== '') {
            } elseif ((string) Request::get(self::LANGUAGE_URL_VARNAME, false, '') !== '') {
                $languageId = Request::get(self::LANGUAGE_URL_VARNAME);
            } elseif (isset($_COOKIE[self::LANGUAGE_COOKIE_VARNAME])) {
                $languageId = $_COOKIE[self::LANGUAGE_COOKIE_VARNAME];
            } elseif ($this->getAllBaseUrl()) {
                $ids = array_keys($this->_allBaseUrl, Ddm::getCurrentBaseUrl());
                if (isset($ids[1])) {
                    $defaultLanguageId = (int) Ddm::getConfig()->getConfigValue('web/language/default_language', 0);
                    $languageId = in_array($defaultLanguageId, $ids) ? $defaultLanguageId : $ids[0];
                } elseif (isset($ids[0])) {
                    $languageId = $ids[0];
                }
            }
            $this->setReadOnlyProperty(['language_id', 'language_code'], true);
            $this->loadFromId($languageId);
            if (!$this->language_code) {
                $this->loadFromId(0);
            }
            $this->setReadOnlyProperty(['language_id', 'language_code']);
            if ($this->_isSaveToCookie && !Ddm::getConfig()->getConfigValue('web/language/inc_url', 0) && $this->getAllLanguage(false) || isset($_COOKIE[self::LANGUAGE_COOKIE_VARNAME])) {
                $this->_saveToCookie($this->language_code);
            }
        }
        return $this;
    }

    /**
     * @param int|string $languageId 可以是数字自增ID或者字符串Code
     * @return $this
     */
    public function loadFromId(int|string $languageId): static
    {
        if (!$languageId || $languageId === '0') {
            return $this->addData($this->getDefaultLanguage())->setOrigData($this->getDefaultLanguage(), null, true);
        }
        if ($allLanguage = $this->getAllLanguage(false)) {
            if (isset($allLanguage[$languageId])) {
                $this->addData($allLanguage[$languageId])->setOrigData($allLanguage[$languageId], null, true);
            } else {
                foreach ($allLanguage as $language) {
                    if ($language['language_id'] == $languageId) {
                        $this->addData($language)->setOrigData($language, null, true);
                        break;
                    }
                }
            }
        }
        return $this;
    }

    /**
     * @param int $id
     * @param string|null $field
     * @return \Language\Model\Language
     */
    public function load(int $id, ?string $field = null): Language
    {
        return !$id && ($field === null || $field == $this->getResource()->getPrimarykey()) ? $this->loadFromId(0) : parent::load($id, $field);
    }

    /**
     * 获取首页URL
     * @return string
     */
    public function getHomeUrl(): string
    {
        return $this->getBaseUrl('home');
    }

    /**
     * @param string $type
     * @return string
     */
    public function getBaseUrl(string $type = 'link'): string
    {
        if (!isset($this->_baseUrlCache[$type])) {
            $languageCode = $this->language_code or $languageCode = Ddm::getLanguage()->language_code;
            switch ($type) {
                case 'home':
                    $this->_baseUrlCache[$type] = Ddm::getConfig()->getConfigValue('web/base/web_url', $this->language_id) or $this->_baseUrlCache[$type] = Ddm::getCurrentBaseUrl();
                    if ($this->isUrlIncludeLanguage()) {
                        if (!Ddm::getConfig()->getConfigValue('web/base/enable_rewrite', $this->language_id)) {
                            if (!Ddm::getConfig()->getConfigValue('web/base/hide_indexphp', $this->language_id)) {
                                $this->_baseUrlCache[$type] .= 'index.php';
                            }
                            $this->_baseUrlCache[$type] .= '?';
                        }
                        $this->_baseUrlCache[$type] .= $languageCode . '/';
                    }
                    break;
                case 'link':
                    $this->_baseUrlCache[$type] = $this->getBaseUrl('home');
                    if (!Ddm::getConfig()->getConfigValue('web/base/enable_rewrite', $this->language_id) && !$this->isUrlIncludeLanguage()) {
                        if (!Ddm::getConfig()->getConfigValue('web/base/hide_indexphp', $this->language_id)) {
                            $this->_baseUrlCache[$type] .= 'index.php';
                        }
                        $this->_baseUrlCache[$type] .= '?';
                    }
                    break;
                case 'css':
                case 'js':
                case 'image':
                case 'images':
                    $this->_baseUrlCache[$type] = Ddm::getConfig()->getConfigValue('web/base/' . $type . '_url', $this->language_id) or $this->_baseUrlCache[$type] = Ddm::getCurrentBaseUrl();
                    break;
                default:
                    $this->_baseUrlCache[$type] = Ddm::getConfig()->getConfigValue('web/base/web_url', $this->language_id) or $this->_baseUrlCache[$type] = Ddm::getCurrentBaseUrl();
            }
        }
        return $this->_baseUrlCache[$type];
    }

    /**
     * @param string $path
     * @param array $params
     * @param bool $folderStyle
     * @return string
     */
    public function getUrl(string $path, array $params = [], bool $folderStyle = false): string
    {
        $url = $this->getBaseUrl('link');
        $urlAlias = Controller::singleton()->getUrlAlias() && !empty($params['_use_alias']) ? Controller::singleton()->getUrlAlias() : null;
        if ($urlAlias) {
            return $url . $this->_parseGetUrlParams($urlAlias, $params, $folderStyle);
        }
        $path = $path == '' ? '*/*' : trim($path, '/');
        $adminPath = Ddm::getConfig()->getConfigValue('admin_path', $this->language_id);
        $paths = explode('/', $path, 3);
        $this->_getUrlPathInfo();
        if ($params && $folderStyle) {
            isset($paths[1]) or $paths[1] = 'index';
            isset($paths[2]) or $paths[2] = 'index';
        }
        foreach ($paths as $k => $p) {
            $url .= $p == '*' || $p == '' ? $this->_urlPathInfo[$k] ? $this->_urlPathInfo[$k] . '/' : '' : ($k == 0 && $p == 'admin' && $adminPath ? $adminPath : $p) . '/';
        }
        if ($params) {
            $url .= $this->_parseGetUrlParams('', $params, $folderStyle);
        }
        return $url;
    }

    /**
     * 格式: [languageId => url, ...]
     * @return array
     */
    public function getAllBaseUrl(): array
    {
        if ($this->_allBaseUrl === null) {
            $this->_allBaseUrl = Cache::load('all_base_url');
            if ($this->_allBaseUrl === false) {
                if ($this->_allBaseUrl = $this->getResource()->getAllBaseUrl()) {
                    foreach ($this->getAllLanguage() as $language) {
                        isset($this->_allBaseUrl[$language['language_id']]) or $this->_allBaseUrl[$language['language_id']] = $this->_allBaseUrl[0];
                    }
                } else {
                    $this->_allBaseUrl = [];
                }
                Cache::save('all_base_url', $this->_allBaseUrl, ['language', 'config'], 0);
            }
        }
        return $this->_allBaseUrl;
    }

    /**
     * @return $this
     */
    public function clearBaseUrlCache(): static
    {
        $this->_baseUrlCache = [];
        return $this;
    }

    /**
     * @param bool $flag
     * @return $this
     */
    public function isSaveToCookie(bool $flag): static
    {
        $this->_isSaveToCookie = (bool) $flag;
        return $this;
    }

    /**
     * @return bool
     */
    public function isUrlIncludeLanguage(): bool
    {
        return Ddm::getConfig()->getConfigValue('web/language/inc_url', 0) && $this->getAllLanguage(false);
    }

    /**
     * @param string|int $languageId
     * @return $this
     */
    protected function _saveToCookie(string|int $languageId): static
    {
        if (!isset($_COOKIE[self::LANGUAGE_COOKIE_VARNAME]) || $_COOKIE[self::LANGUAGE_COOKIE_VARNAME] != $languageId) {
            $_COOKIE[self::LANGUAGE_COOKIE_VARNAME] = $languageId;
            setcookie(self::LANGUAGE_COOKIE_VARNAME, $languageId, Request::server()->REQUEST_TIME + 604800, '/');
        }
        return $this;
    }

    /**
     * @return array
     */
    protected function _getUrlPathInfo(): array
    {
        if ($this->_urlPathInfo === null) {
            $adminPath = Ddm::getConfig()->getConfigValue('admin_path', $this->language_id);
            $this->_urlPathInfo = [Controller::singleton()->getModuleName() == 'admin' && $adminPath ? $adminPath : Controller::singleton()->getModuleName(), Controller::singleton()->getControllerName(), Controller::singleton()->getActionName()];
        }
        return $this->_urlPathInfo;
    }

    /**
     * @param string $urlAlias
     * @param array $params
     * @param bool $folderStyle
     * @return string
     */
    protected function _parseGetUrlParams(string $urlAlias, array $params, bool $folderStyle = false): string
    {
        $url = $urlAlias;
        $queryString = '';
        if (!$folderStyle && $urlAlias && (stripos($urlAlias, '.html') || stripos($urlAlias, '.htm'))) {
            $folderStyle = true;
        }
        if (isset($params['_current']) && $params['_current']) {
            $params = array_merge(Request::singleton()->getParams(false, false), $params);
            $queryString = $this->_parseQueryStringToParams();
            unset($params['_current'], $params[self::LANGUAGE_URL_VARNAME]);
        }
        if (isset($params['_query']) && $params['_query']) {
            is_array($params['_query']) and $params['_query'] = http_build_query($params['_query']);
            $queryString .= $queryString || !Ddm::getConfig()->getConfigValue('web/base/enable_rewrite', $this->language_id) ? '&' : ($queryString ? '&' : '?');
            $queryString .= $params['_query'];
            unset($params['_query']);
        }
        if (isset($params['_use_alias'])) {
            unset($params['_use_alias']);
        }
        if ($params) {
            $paramUrl = '';
            $delimiter = $folderStyle ? '/' : '-';
            foreach ($params as $key => $value) {
                if ((string) $value !== '') {
                    $paramUrl == '' or $paramUrl .= $delimiter;
                    $paramUrl .= $key . $delimiter . $value;
                }
                if ($queryString && strpos($queryString, "{$key}=") !== false) {
                    $queryString = preg_replace('/[&\?]*\b' . preg_quote($key, '/') . '=[^&]*/i', '', $queryString);
                }
            }
            if ($paramUrl) {
                if ($url && substr($url, -1) != '/') {
                    $url .= '/';
                }
                $url .= $folderStyle ? "{$paramUrl}/" : "{$paramUrl}.html";
            }
            if ($queryString) {
                $queryString = ltrim($queryString, '?&');
                $queryString = (strpos($url, '?') === false ? '?' : '&') . $queryString;
            }
        }
        return $url . $queryString;
    }

    /**
     * @return string
     */
    protected function _parseQueryStringToParams(): string
    {
        $queryString = '';
        if (Request::server()->QUERY_STRING) {
            if (Ddm::getConfig()->getConfigValue('web/base/enable_rewrite', $this->language_id)) {
                $queryString = '?' . Request::server()->QUERY_STRING;
            } elseif ($i = strpos(Request::server()->QUERY_STRING, '&')) {
                $queryString = substr(Request::server()->QUERY_STRING, $i);
            }
        }
        return $queryString;
    }

    protected function _beforeSave(): static
    {
        Ddm::dispatchEvent('language_save_before', ['object' => $this]);
        return parent::_beforeSave();
    }

    protected function _afterSave(): static
    {
        Ddm::dispatchEvent('language_save_after', ['object' => $this]);
        Cache::singleton()->removeByTags(['language']);
        return parent::_afterSave();
    }

    protected function _beforeDelete(): static
    {
        Ddm::dispatchEvent('language_delete_before', ['object' => $this]);
        return parent::_beforeDelete();
    }

    protected function _afterDelete(): static
    {
        Ddm::dispatchEvent('language_delete_after', ['object' => $this]);
        Cache::clear();
        //删除全部缓存
        Ddm::getTranslate()->removeTranslate($this->getId());
        //删除该语言的翻译文件
        return parent::_afterDelete();
    }
}

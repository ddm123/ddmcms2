<?php

namespace Core\Model;

use Ddm;
use Ddm\Db;
use Ddm\Request;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Url
{

    protected static mixed $_instance = null;

    protected mixed $_mainTable = null;

    public function __construct()
    {
        //;
    }

    /**
     * 使用单例模式
     * @return \Core\Model\Url
     */
    public static function singleton(): Url
    {
        return self::$_instance === null ? self::$_instance = new self() : self::$_instance;
    }

    /**
     * @return string
     */
    public function getMainTable(): string
    {
        return $this->_mainTable === null ? $this->_mainTable = Db::getTable('url_index') : $this->_mainTable;
    }

    /**
     * @param string $urlKey
     * @param string $path
     * @param array $params
     * @return string
     */
    public function getUrl(string $urlKey, string $path, array $params = []): string
    {
        if ($urlKey) {
            $moduleName = explode('/', $path, 2);
            $url = Ddm::getBaseUrl() . $moduleName[0] . '/' . $urlKey;
        } else {
            $url = Ddm::getUrl($path, $params);
        }
        return $url;
    }

    /**
     * @param array $urlKey
     * @param string $module
     * @param string $controller
     * @param string $action
     * @param array $params
     * @return \Core\Model\Url
     */
    public function saveUrl(array $urlKey, string $module, string $controller = '', string $action = '', ?array $params = null): Url
    {
        if ($urlKey && $module) {
            if ($params !== null) {
                $params = is_array($params) ? serialize($params) : null;
            }
            $data = [];
            foreach ($urlKey as $languageId => $_urlKey) {
                if (!$_urlKey && $_urlKey !== '0') {
                    continue;
                }
                $data[] = ['url_path' => "{$module}/{$_urlKey}", 'language_id' => (int) $languageId, 'module' => (string) $module, 'controller' => (string) $controller, 'action' => (string) $action, 'params' => (string) $params];
            }
            if ($data) {
                Db::getWriteConn()->insertMultiple($this->getMainTable(), $data, false, ['module', 'controller', 'action', 'params']);
            }
        }
        return $this;
    }

    /**
     * @param array $urlKey
     * @param int $languageId
     * @param string $module
     * @param string $controller
     * @param string $action
     * @param array $params
     * @return \Core\Model\Url
     */
    public function saveUrlIndex(array $urlKey, int $languageId, string $module, string $controller = '', string $action = '', ?array $params = null): Url
    {
        if ($urlKey && $module) {
            if ($params !== null) {
                $params = is_array($params) ? serialize($params) : null;
            }
            Db::getWriteConn()->insert($this->getMainTable(), ['url_path' => "{$module}/{$urlKey}", 'language_id' => (int) $languageId, 'module' => (string) $module, 'controller' => (string) $controller, 'action' => (string) $action, 'params' => (string) $params], false, ['module' => (string) $module, 'controller' => (string) $controller, 'action' => (string) $action, 'params' => (string) $params]);
        }
        return $this;
    }

    /**
     * @param string $urlPath
     * @param int $languageId
     * @param array $urlPaths
     * @return array
     */
    public function loadFromUrlPath(string $urlPath, ?int $languageId = null, array &$urlPaths = []): array
    {
        if (!$urlPath) {
            return [];
        }
        $urlPaths = [];
        $k = 0;
        foreach (explode('/', $urlPath) as $p) {
            if (stripos($p, '.html') || stripos($p, '.htm')) {
                $urlPaths[$k] = $k ? $urlPaths[$k - 1] . $p : $p;
                $urlPaths[1 + $k] = $urlPaths[$k] . '/';
                break;
            } else {
                $urlPaths[$k] = $k ? $urlPaths[$k - 1] . $p . '/' : $p . '/';
                $k++;
            }
        }
        //如果斜杠超过5个以上则认为这并不是后台填写的url_key
        if (isset($urlPaths[2]) && ($urlPaths = array_slice($urlPaths, 1, -1, false)) && !isset($urlPaths[5])) {
            $builer = Db::table($this->getMainTable(), true);
            $builer->where('language_id', '=', $languageId === null ? Ddm::getLanguage()->language_id : (int) $languageId);
            isset($urlPaths[1]) ? $builer->whereIn('url_path', $urlPaths) : $builer->where('url_path', '=', $urlPaths[0]);
            return $builer->get(null, 'url_path');
        }
        return [];
    }

    /**
     * @param string $urlPath
     * @param mixed $languageId
     * @return $this
     */
    public function removeFromUrlPath(string $urlPath, mixed $languageId = true): static
    {
        $builder = Db::table($this->getMainTable(), true);
        $builder->where('url_path', '=', $urlPath);
        if ($languageId === null) {
            $languageId = Ddm::getLanguage()->language_id;
        }
        if ($languageId !== true) {
            $builder->where('language_id', '=', $languageId);
        }
        $builder->delete();
        return $this;
    }

    /**
     * @param string $module
     * @param string $urlKey
     * @param mixed $languageId
     * @return \Core\Model\Url
     */
    public function removeFromUrlKey(string $module, string $urlKey, mixed $languageId = true): Url
    {
        return $this->removeFromUrlPath("{$module}/{$urlKey}", $languageId);
    }

    /**
     * @param array $params
     * @return $this
     */
    public function deleteFromLanguage(array $params): static
    {
        if ($languageId = (int) $params['object']->getId()) {
            Db::table($this->getMainTable(), true)->where('language_id', '=', $languageId)->delete();
        }
        return $this;
    }

    /**
     * @param array $params
     * @return $this
     */
    public function matchesUrl(array $params): static
    {
        $urlPaths = [];
        if (($urlPath = $params['controller']->getSelfPath()) && $data = $this->loadFromUrlPath($urlPath, null, $urlPaths)) {
            for ($i = count($urlPaths); $i--;) {
                if (isset($data[$urlPaths[$i]])) {
                    $data = $data[$urlPaths[$i]];
                    break;
                }
            }
            $params['controller']->setModuleName($data['module'])->setControllerName($data['controller'] ? $data['controller'] : null)->setActionName($data['action'] ? $data['action'] : null)->parseParams(substr($urlPath, strlen($data['url_path'])))->setUrlAlias($data['url_path']);
            $params['vars']->parseUriResult = true;
            if (isset($_GET[$params['vars']->url])) {
                unset($_GET[$params['vars']->url]);
            }
            $params['controller']->setHomePage(false);
            $params['controller']->resetRunModule();
            if ($data['params']) {
                foreach (unserialize($data['params']) as $key => $value) {
                    Request::singleton()->setParam($key, $value);
                }
            }
        }
        return $this;
    }
}

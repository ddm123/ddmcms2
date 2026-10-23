<?php

namespace Ddm;

use Ddm;
use Language\Model\Language;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Controller
{

    protected static mixed $_instance = null;

    protected ?string $_rootPath = null;

    protected ?string $_currentBaseUrl = null;

    protected ?string $_moduleName = null;

    protected ?string $_controllerName = null;

    protected ?string $_actionName = null;

    private ?string $_currentUrl = null;

    private ?bool $_parseUriResult = null;

    private ?string $_urlAlias = null;

    private bool $_isHomePage = true;

    private ?bool $_setIsHomePage = null;

    private ?bool $_canRunModule = null;

    private ?string $_uriPath = null;

    private ?string $_selfPath = null;

    private ?string $_404PageHtml = null;

    private ?string $_403PageHtml = null;

    private ?bool $_checkAccessResult = null;

    private function __construct()
    {
        //
    }

    /**
     * 使用单例模式
     *
     * @return \Ddm\Controller
     */
    public static function singleton(): Controller
    {
        return self::$_instance === null ? self::$_instance = new static() : self::$_instance;
    }

    /**
     * @return string
     */
    protected function _getCurrentUrl(): string
    {
        if ($this->_currentUrl === null) {
            $this->_currentUrl = preg_replace('/^(' . preg_quote($this->getRootPath(), '/') . ')(?:\?|index\.php)(?:\?\/|\/|\?)*/i', '\1', Request::server()->REQUEST_URI, 1);
            if ($this->_currentUrl) {
                $this->_currentUrl = substr($this->_currentUrl, strlen($this->getRootPath()));
            }
        }
        return $this->_currentUrl;
    }

    /**
     * @param array $matchPath
     * @return $this
     */
    private function _parseRouting(array $matchPath): static
    {
        $this->setModuleName($matchPath[1]);
        if (isset($matchPath[2]) && $matchPath[2] = trim($matchPath[2], '/')) {
            $matchPath[2] = explode('/', $matchPath[2]);
            $length = count($matchPath[2]);
            for ($i = 0, $j = 0; $i < $length; $i++) {
                if ($matchPath[2][$i]) {
                    if ($j == 0 && !stripos($matchPath[2][$i], '.html')) {
                        $this->setControllerName($matchPath[2][$i]);
                    } elseif ($j == 1 && !stripos($matchPath[2][$i], '.html')) {
                        $this->setActionName($matchPath[2][$i]);
                    } elseif ($this->_parseParams($matchPath[2], $i)) {
                        break;
                    }
                    $j++;
                }
            }
        }
        return $this;
    }

    /**
     * @param array $params
     * @param int $i
     * @return bool
     */
    private function _parseParams(array $params, int &$i = 0): bool
    {
        $result = false;
        $k = $params[$i];
        $v = isset($params[++$i]) ? $params[$i] : '';
        if ($i1 = stripos($k, '.html') or $i2 = stripos($v, '.html')) {
            $p2 = explode('-', substr($i1 ? $k : $v, 0, $i1 ? $i1 : $i2));
            if (isset($p2[1])) {
                foreach ($p2 as $x => $_p) {
                    if ($x % 2 == 0) {
                        Request::singleton()->setParam($_p, isset($p2[1 + $x]) ? $p2[1 + $x] : '');
                    }
                }
            }
            $result = true;
        } else {
            Request::singleton()->setParam($k, $v);
        }
        return $result;
    }

    /**
     * @param string $className
     * @return bool
     */
    private function _callAction(string $className): bool
    {
        $result = false;
        $className = ltrim($className, '\\');
        $classFile = SITE_ROOT . '/' . MODULES_FOLDER . '/' . str_replace('\\', '/', $className);
        if (!is_file("{$classFile}.php")) {
            $classFile = "{$classFile}/Index";
            $className .= '\Index';
        }
        if (is_file("{$classFile}.php")) {
            require "{$classFile}.php";
            $controller = new $className();
            Ddm::dispatchEvent('controller_action_before', ['controller' => $this, 'object' => $controller]);
            $controller->callActionBefore($this);
            //把减号替换掉
            $action = $this->_actionName ? preg_replace_callback('/\-([A-Za-z])/', [$this, '_parseActionNameCallback'], $this->_actionName) . 'Action' : 'indexAction';
            if (is_callable([$controller, $action])) {
                if ($controller->isAllowed($action)) {
                    $controller->{$action}();
                    Ddm::dispatchEvent('controller_action_after', ['controller' => $this, 'object' => $controller, 'action' => $action]);
                    $controller->callActionAfter($this, $action);
                    $result = true;
                } else {
                    $controller->noPermission($this);
                }
                $this->_canRunModule = true;
            }
        }
        return $result;
    }

    /**
     * @param array $matches
     * @return string
     */
    protected function _parseActionNameCallback(array $matches): string
    {
        return strtoupper($matches[1]);
    }

    /**
     * @param array $ips
     * @param string $ip
     * @return bool
     */
    protected function _checkExistsIp(array $ips, string $ip): bool
    {
        foreach ($ips as $_ip) {
            if (preg_match('/' . str_replace(['.', '*'], ['\.', '\d+'], $_ip) . '/', $ip)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return bool
     */
    public function checkAccessResult(): bool
    {
        if ($this->_checkAccessResult === null) {
            $this->_checkAccessResult = true;
            $ips = Ddm::getConfigValue('system/access/ip_blacklist');
            if ($ips && Request::server()->REMOTE_ADDR) {
                if ($ips == '*' || strpos($ips, Request::server()->REMOTE_ADDR) !== false) {
                    $this->_checkAccessResult = false;
                } elseif (strpos($ips, '*') !== false && preg_match_all('/[\d\.\*]+/', $ips, $matches)) {
                    $this->_checkAccessResult = !$this->_checkExistsIp($matches[0], Request::server()->REMOTE_ADDR);
                }
            }
        }
        return $this->_checkAccessResult;
    }

    /**
     * @param string $path
     * @return $this
     */
    public function setUriPath(string $path): static
    {
        $this->_uriPath = $path;
        return $this;
    }

    /**
     * @param string $alias
     * @return $this
     */
    public function setUrlAlias(string $alias): static
    {
        $this->_urlAlias = $alias;
        return $this;
    }

    /**
     * @param string $moduleName
     * @return $this
     */
    public function setModuleName(string $moduleName): static
    {
        $this->_moduleName = $moduleName;
        return $this;
    }

    /**
     * @param string $controllerName
     * @return $this
     */
    public function setControllerName(string $controllerName): static
    {
        $this->_controllerName = $controllerName;
        return $this;
    }

    /**
     * @param string $actionName
     * @return $this
     */
    public function setActionName(string $actionName): static
    {
        $this->_actionName = $actionName;
        return $this;
    }

    /**
     * @return string
     */
    public function getModuleName(): string
    {
        return $this->_moduleName;
    }

    /**
     * @return string
     */
    public function getControllerName(): string
    {
        return $this->_controllerName;
    }

    /**
     * @return string
     */
    public function getActionName(): string
    {
        return $this->_actionName;
    }

    /**
     * @return string
     */
    public function getUriPath(): string
    {
        if ($this->_uriPath === null) {
            $this->_uriPath = '';
            if ($url = $this->_getCurrentUrl()) {
                $p = explode('/', $url, 2);
                if (strpos($p[0], '=') === false) {
                    if (Ddm::getLanguage()->isUrlIncludeLanguage()) {
                        $allLanguage = Ddm::getLanguage()->getAllLanguage(true);
                        if (isset($allLanguage[$p[0]])) {
                            Request::singleton()->setParam(Language::LANGUAGE_URL_VARNAME, $p[0]);
                            array_shift($p);
                        }
                    }
                    $this->_uriPath = implode('/', $p);
                }
            }
        }
        return $this->_uriPath;
    }

    /**
     * @return string
     */
    public function getSelfPath(): string
    {
        if ($this->_selfPath === null) {
            $uri = $this->getUriPath();
            $i = strpos($uri, '?');
            $i === false and $i = strpos($uri, '&');
            if ($i === false) {
                $this->_selfPath = $uri;
            } else {
                $this->_selfPath = substr($uri, 0, $i);
            }
        }
        return $this->_selfPath;
    }

    /**
     * @return string|null
     */
    public function getUrlAlias(): ?string
    {
        return $this->_urlAlias;
    }

    /**
     * @return bool
     */
    public function checkAccess(): bool
    {
        if (!$this->checkAccessResult()) {
            switch (Ddm::getConfigValue('system/access/no_access_message')) {
                case '403':
                    self::outputNoPermissionActionHeader();
                    echo $this->get403PageHtml();
                    break;
                case '404':
                    self::outputNoActionHeader();
                    echo $this->get404PageHtml();
                    break;
                default:
                    echo $this->getBlankPageHtml(Ddm::getConfigValue('system/access/no_access_message'), '');
            }
            return false;
        }
        return true;
    }

    /**
     * 是否可以解析当前访问的URL结果
     */
    public function parseUri(): bool
    {
        if ($this->_parseUriResult === null) {
            $this->_parseUriResult = false;
            $this->_isHomePage = true;
            if (($url = $this->_getCurrentUrl()) && $this->getUriPath()) {
                $obj = new \stdClass();
                $obj->parseUriResult = $this->_parseUriResult;
                $obj->url = $url;
                Ddm::dispatchEvent('controller_parse_routing_before', ['controller' => $this, 'vars' => $obj]);
                $this->_parseUriResult = $obj->parseUriResult;
                $url = $obj->url;
                if (!$this->_parseUriResult && preg_match('/^(\w+)(\/[^\?&]*)?/', $this->_uriPath, $p)) {
                    $this->_isHomePage = false;
                    if (isset($_GET[$url])) {
                        unset($_GET[$url]);
                    }
                    $this->_parseRouting($p);
                    $this->_parseUriResult = true;
                }
            }
        }
        return $this->_parseUriResult;
    }

    /**
     * @param string $path
     * @return $this
     */
    public function parseParams(string $path): static
    {
        if ($path and $path = trim($path, '/')) {
            $path = explode('/', $path);
            $length = count($path);
            for ($i = 0, $j = 0; $i < $length; $i++) {
                if ($this->_parseParams($path, $i)) {
                    break;
                }
            }
        }
        return $this;
    }

    /**
     * 获取当前浏览器地址栏上显示的域名,末尾包含斜杠
     */
    public function getCurrentBaseUrl(): string
    {
        if ($this->_currentBaseUrl === null) {
            $this->_currentBaseUrl = !empty(Request::server()->HTTPS) && 'off' != Request::server()->HTTPS ? 'https://' : 'http://';
            $this->_currentBaseUrl .= empty(Request::server()->SERVER_NAME) ? Request::server()->HTTP_HOST : Request::server()->SERVER_NAME;
            Request::server()->SERVER_PORT == 80 or $this->_currentBaseUrl .= ':' . Request::server()->SERVER_PORT;
            $this->_currentBaseUrl .= $this->getRootPath();
        }
        return $this->_currentBaseUrl;
    }

    /**
     * @return string
     */
    public function getRootPath(): string
    {
        if ($this->_rootPath === null) {
            $this->_rootPath = '/';
            $uri = Request::server()->REQUEST_URI;
            foreach (explode('/', SITE_ROOT) as $p) {
                if (strpos($uri, "/{$p}/") === 0) {
                    $this->_rootPath .= "{$p}/";
                    $uri = substr($uri, strlen($this->_rootPath) - 1);
                }
            }
        }
        return $this->_rootPath;
    }

    /**
     * 当前访问的是否网站首页
     */
    public function isHomePage(): bool
    {
        return $this->_setIsHomePage === null ? $this->_isHomePage : $this->_setIsHomePage;
    }

    /**
     * @param bool $flag
     * @return $this
     */
    public function setHomePage(bool $flag): static
    {
        $this->_setIsHomePage = (bool) $flag;
        return $this;
    }

    /**
     * @return bool
     */
    public function runModule(): bool
    {
        if ($this->_canRunModule === null) {
            $this->_canRunModule = false;
            if (!$this->_moduleName) {
                return $this->_canRunModule;
            }
            $this->_isHomePage = false;
            $adminModuleName = Ddm::getConfig()->getConfigValue('admin_path');
            $canRunModule = true;
            if ($adminModuleName == $this->_moduleName) {
                $this->setModuleName('admin');
            } elseif ($adminModuleName && strtolower($this->_moduleName) == 'admin') {
                $canRunModule = false;
            }
            if ($canRunModule && Ddm::getEnabledModuleConfig($this->_moduleName)) {
                Ddm::dispatchEvent('controller_module_before', ['controller' => $this]);
                $controllerName = $this->_controllerName ? implode('\\', array_map('ucfirst', explode('-', $this->_controllerName))) : 'Index';
                $this->_callAction('\\' . ucfirst($this->_moduleName) . '\Controller\\' . $controllerName);
            }
        }
        return $this->_canRunModule;
    }

    /**
     * @return $this
     */
    public function resetRunModule(): static
    {
        $this->_canRunModule = null;
        return $this;
    }

    /**
     * @return void
     */
    public function run(): void
    {
        try {
            if ($this->checkAccess()) {
                if ($this->parseUri() && $this->runModule()) {
                    return;
                }
                Ddm::dispatchEvent('controller_module_notfound', ['controller' => $this]);
                if ($this->runModule()) {
                    return;
                }
                if ($this->isHomePage()) {
                    //Do something
                } else {
                    self::noAction();
                }
            }
        } catch (\Exception $exception) {
            Ddm::dispatchEvent('catch_controller_error_exception', ['controller' => $this, 'exception' => $exception]);
            $exceptionLog = $exception->getMessage() . PHP_EOL . $exception->getTraceAsString();
            \Ddm\Log::saveTo('exception.log', $exceptionLog . PHP_EOL . 'URL: ' . \Ddm\Log::getCurrentUrl(true));

            if (Ddm::$enableDebug) {
                echo "<pre>{$exceptionLog}</pre>";
            }
        }
    }

    /**
     * @param string $html
     * @return $this
     */
    public function set404PageHtml(string $html): static
    {
        $this->_404PageHtml = $html;
        return $this;
    }

    /**
     * @return string
     */
    public function get404PageHtml(): string
    {
        if ($this->_404PageHtml === null) {
            $this->_404PageHtml = '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>404 Not Found</title>
</head>
<body>
<h1>Not Found</h1>
<p>The requested URL was not found on this server.</p>
</body>
</html>';
        }
        return $this->_404PageHtml;
    }

    /**
     * @param string $html
     * @return $this
     */
    public function set403PageHtml(string $html): static
    {
        $this->_403PageHtml = $html;
        return $this;
    }

    /**
     * @return string
     */
    public function get403PageHtml(): string
    {
        if ($this->_403PageHtml === null) {
            $this->_403PageHtml = '<!DOCTYPE HTML><html><head><title>403 Forbidden</title></head><body><h1>403 Forbidden</h1><p>You don\'t have permission to access</p></body></html>';
        }
        return $this->_403PageHtml;
    }

    /**
     * @param string $body
     * @param string $title
     * @return string
     */
    public function getBlankPageHtml(string $body, string $title = ''): string
    {
        return '<!DOCTYPE HTML><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><title>' . ($title ? $title . ' - ' : '') . Ddm::getConfigValue('web/base/web_name') . '</title></head><body>' . ($title ? '<h1>' . $title . '</h1>' : '') . '<div>' . $body . '</div></body></html>';
    }

    /**
     * Display 404 Not Found page
     * @return void
     */
    public static function noAction(): void
    {
        self::outputNoActionHeader();
        //你可以在根目录下放一个404.php文件来自定义显示404错误页
        if (file_exists(SITE_ROOT . '/404.php')) {
            include SITE_ROOT . '/404.php';
        } else {
            Ddm::dispatchEvent('controller_call_noaction_before', ['controller' => self::singleton()]);
            echo self::singleton()->get404PageHtml();
        }
    }

    /**
     * Display 403 Forbidden page
     * @return void
     */
    public static function noPermissionAction(): void
    {
        self::outputNoPermissionActionHeader();
        Ddm::dispatchEvent('controller_call_nopermissionaction_before', ['controller' => self::singleton()]);
        echo self::singleton()->get403PageHtml();
    }

    /**
     * @return void
     */
    public static function outputNoActionHeader(): void
    {
        header("HTTP/1.0 404 Not Found");
        header("Status: 404 Not Found");
        header("X-Cms-Notfound: True");
        //头信息加上这个标识是CMS系统抛出的404,而不是Web Server抛出的
    }

    /**
     * @return void
     */
    public static function outputNoPermissionActionHeader(): void
    {
        header("HTTP/1.1 403 Forbidden");
        header("Status: 403 Forbidden");
    }
}

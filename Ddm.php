<?php

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

$_SERVER['START_TIME'] = microtime(true);
define('_VERSION_', '2.0.0');
define('GET_MQG', false);
define('SITE_ROOT', __DIR__);
define('MODULES_FOLDER', 'modules');
define('DESIGN_FOLDER', 'design');
defined('E_RECOVERABLE_ERROR') or define('E_RECOVERABLE_ERROR', 4096);
defined('E_DEPRECATED') or define('E_DEPRECATED', 8192);
defined('E_USER_DEPRECATED') or define('E_USER_DEPRECATED', 16384);
set_include_path(
    SITE_ROOT . DIRECTORY_SEPARATOR . 'lib' . PATH_SEPARATOR
    . SITE_ROOT . DIRECTORY_SEPARATOR . MODULES_FOLDER . PATH_SEPARATOR
    . SITE_ROOT . DIRECTORY_SEPARATOR . 'install'
);

use Config\Model\Config;
use Language\Model\Language;
use Ddm\Log;

final class Ddm
{

    public const MODULES_CONFIG_CACHE_KEY = 'all_modules';

    public const MODULE_CONFIG_CACHE_KEY_PRE = 'module_config_';

    private static array $_versionCompare = [];

    private static bool $_initialized = false;

    private static ?bool $_isInstalled = null;

    private static array $_registry = [];

    private static null|bool|array $_allModules = null;

    private static ?Config $_config = null;

    private static ?Language $_language = null;

    /** @var array<string|int, Language> */
    private static array $_languages = [];

    private static ?\Language\Model\Helper $_translate = null;

    private static ?array $_events = null;

    private static array $_eventListener = [];

    private static array $_helpers = [];

    private static array $_sessions = [];

    public static bool $enableDebug = true;

    private function __construct()
    {
        //私有的构造方法
    }

    /**
     * 判断当前PHP版本是否大于或等于某个版本号
     *
     * @param string $compareVersion
     * @return bool
     */
    public static function leastThisVersion(string $compareVersion): bool
    {
        return isset(self::$_versionCompare[$compareVersion])
            ? self::$_versionCompare[$compareVersion]
            : (self::$_versionCompare[$compareVersion] = version_compare($compareVersion, PHP_VERSION, '<='));
    }

    /**
     * 获取当前浏览器地址栏上显示的域名,末尾包含斜杠
     *
     * @return string
     */
    public static function getCurrentBaseUrl(): string
    {
        return Ddm\Controller::singleton()->getCurrentBaseUrl();
    }

    /**
     * 获取首页URL, 末尾包含斜杠
     *
     * @return string
     */
    public static function getHomeUrl(): string
    {
        return self::$_language->getBaseUrl('home');
    }

    /**
     * 如果你是想在获取首页的链接后面拼接另一个路径作为一个的新链接, 就需要使用该方法, 而不是getHomeUrl()方法, 末尾包含斜杠
     *
     * @return string
     */
    public static function getBaseUrl(): string
    {
        return self::$_language->getBaseUrl('link');
    }

    /**
     * 返回一个链接
     *
     * @param string $path 格式: Module/Controller/Action
     * @param array $params
     * @param bool $folderStyle
     * @return string
     */
    public static function getUrl(string $path, array $params = [], bool $folderStyle = false): string
    {
        return self::$_language->getUrl($path, $params, $folderStyle);
    }

    /**
     * 获取在后台配置的网站名称
     *
     * @return string
     */
    public static function getSiteName(): string
    {
        return self::$_config->getConfigValue('web/base/web_name');
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param bool $graceful
     * @throws Exception
     * @return void
     */
    public static function register(string $key, mixed $value, bool $graceful = false): void
    {
        if (isset(self::$_registry[$key])) {
            if ($graceful) {
                return;
            }
            throw new Exception('Registry key "' . $key . '" already exists.');
        }
        self::$_registry[$key] = $value;
    }

    /**
     * @param string $key
     * @return mixed
     * @return void
     */
    public static function registry(string $key): mixed
    {
        return self::$_registry[$key] ?? null;
    }

    /**
     * @param string $key
     * @return void
     */
    public static function unregister(string $key): void
    {
        if (isset(self::$_registry[$key])) {
            if (is_object(self::$_registry[$key]) && method_exists(self::$_registry[$key], '__destruct')) {
                self::$_registry[$key]->__destruct();
            }
            unset(self::$_registry[$key]);
        }
    }

    /**
     * 临时监听一些事件, 这和在config.xml定义的事件监听不一样: 在config.xml定义的, 只要监听的事件被触发都能监听到
     * 这对于在访问某个页面的时候才需要对某个事件做一些处理很有用
     *
     * @param string $eventName
     * @param callable $callback
     * @return void
     */
    public static function addEventListener(string $eventName, callable $callback): void
    {
        self::$_eventListener[$eventName] ??= [];
        self::$_eventListener[$eventName][] = $callback;
    }

    /**
     * 获取全部在config.xml中定义的事件监听
     *
     * @param string $eventName
     * @return array
     */
    public static function getEvents(?string $eventName = null): array
    {
        if (self::$_events === null) {
            self::$_events = [];
            foreach (self::getModuleConfig() as $config) {
                if ($config['active'] && !empty($config['events'])) {
                    foreach ($config['events'] as $_eventName => $observer) {
                        self::$_events[$_eventName] ??= [];
                        isset($observer[0]) or $observer = [0 => $observer];
                        self::$_events[$_eventName] = array_merge(self::$_events[$_eventName], $observer);
                    }
                }
            }
        }
        if ($eventName !== null) {
            return self::$_events[$eventName] ?? null;
        }
        return self::$_events;
    }

    /**
     * @param string $name
     * @param array $data
     * @return void
     */
    public static function dispatchEvent(string $name, array $data = []): void
    {
        if ($events = self::getEvents($name)) {
            foreach ($events as $observer) {
                $observer['type'] ??= 'new';
                if (!empty($observer['class']) && !empty($observer['method']) && $observer['type'] != 'disabled') {
                    $object = $observer['type'] == 'singleton' ? call_user_func([$observer['class'], 'singleton']) : new $observer['class']();
                    call_user_func([$object, $observer['method']], $data);
                }
            }
        }
        if (isset(self::$_eventListener[$name])) {
            foreach (self::$_eventListener[$name] as $callback) {
                $callback($data);
            }
        }
    }

    /**
     * @return array
     */
    public static function getAllModuleConfig(): array
    {
        if (self::$_allModules === null) {
            self::$_allModules = Ddm\Cache::load(self::MODULES_CONFIG_CACHE_KEY);
            if (self::$_allModules === false) {
                $handle = opendir(SITE_ROOT . DIRECTORY_SEPARATOR . MODULES_FOLDER);
                self::$_allModules = [];
                while ($file = readdir($handle)) {
                    if ($file != '.' && $file != '..' && is_dir(SITE_ROOT . DIRECTORY_SEPARATOR . MODULES_FOLDER . DIRECTORY_SEPARATOR . $file)) {
                        self::$_allModules[$file] = ['module_name' => $file, 'active' => false];
                    }
                }
                closedir($handle);
                Ddm\Cache::save(self::MODULES_CONFIG_CACHE_KEY, self::$_allModules, ['module'], 0);
            }
            foreach (self::$_allModules as $_moduleName => $_value) {
                $moduleConfig = Ddm\Cache::load(self::MODULE_CONFIG_CACHE_KEY_PRE . $_moduleName);
                if ($moduleConfig === false) {
                    $configFile = SITE_ROOT . DIRECTORY_SEPARATOR . MODULES_FOLDER . "/$_moduleName/etc/config.xml";
                    if (is_file($configFile) && $config = Ddm::getConfig()->xmlFileToArray($configFile)) {
                        $config['module_name'] = $_moduleName;
                        self::$_allModules[$_moduleName] = $config;
                    }
                    Ddm\Cache::save(self::MODULE_CONFIG_CACHE_KEY_PRE . $_moduleName, self::$_allModules[$_moduleName], ['module', 'config'], 0);
                } else {
                    self::$_allModules[$_moduleName] = $moduleConfig;
                }
            }
        }
        return self::$_allModules;
    }

    /**
     * @param string $moduleName
     * @return array|null
     */
    public static function getModuleConfig(?string $moduleName = null): ?array
    {
        if ($moduleName === null) return self::getAllModuleConfig();

        self::$_allModules === null and self::getAllModuleConfig();
        return isset(self::$_allModules[$moduleName = ucfirst($moduleName)]) ? self::$_allModules[$moduleName] : null;
    }

    /**
     * @param string $moduleName
     * @return array|null
     */
    public static function getEnabledModuleConfig(string $moduleName): ?array
    {
        $mc = self::getModuleConfig($moduleName);
        return $mc && !empty($mc['active']) ? $mc : null;
    }

    /**
     * @return \Config\Model\Config|null
     */
    public static function getConfig(): ?Config
    {
        return self::$_config;
    }

    /**
     * @param string $path
     * @param int|null $languageId
     * @return string|null
     */
    public static function getConfigValue(string $path, ?int $languageId = null): ?string
    {
        return self::$_config->getConfigValue($path, $languageId);
    }

    /**
     * @param int|string $languageId 'ID' or 'Code'
     * @return \Language\Model\Language|null
     */
    public static function getLanguage(int|string|null $languageId = null): ?Language
    {
        if ($languageId === null) {
            return self::$_language;
        }
        if (!isset(self::$_languages[$languageId])) {
            $languageId = (string) $languageId;
            if ($languageId == self::$_language->language_id || $languageId == self::$_language->language_code) {
                self::$_languages[$languageId] = self::$_language;
            } else {
                self::$_languages[$languageId] = new Language();
                self::$_languages[$languageId]->loadFromId($languageId);
                //If not exist, then load the default language
                if (!self::$_languages[$languageId]->language_code) {
                    return self::$_languages[$languageId] = self::getLanguage('0');
                }
                $languageCode = $languageId == self::$_languages[$languageId]->language_id
                    ? self::$_languages[$languageId]->language_code
                    : self::$_languages[$languageId]->language_id;
                if ($languageCode != $languageId) {
                    self::$_languages[$languageCode] = self::$_languages[$languageId];
                }
            }
        }
        return self::$_languages[$languageId];
    }

    /**
     * @param string $moduleName
     * @return object
     */
    public static function getHelper(string $moduleName): object
    {
        if (isset(self::$_helpers[$moduleName = ucfirst($moduleName)])) {
            return self::$_helpers[$moduleName];
        }
        $className = '\\' . $moduleName . '\Model\Helper';
        return self::$_helpers[$moduleName] = new $className();
    }

    /**
     * @param string $namespace
     * @return \Ddm\Session
     */
    public static function getSession(?string $namespace = null): Ddm\Session
    {
        if (isset(self::$_sessions[$namespace])) {
            return self::$_sessions[$namespace];
        }
        self::$_sessions[$namespace] = new Ddm\Session();
        self::$_sessions[$namespace]->init($namespace);
        return self::$_sessions[$namespace];
    }

    /**
     * @param string $moduleName
     * @return \Language\Model\Helper
     */
    public static function getTranslate(string $moduleName = 'Core'): \Language\Model\Helper
    {
        return self::$_translate->setModuleName($moduleName);
    }

    /**
     * @param int|string|false $languageId 语言ID或语言Code
     * @return void
     */
    public static function init(int|string|false $languageId = false): void
    {
        if (self::$_initialized == false) {
            try {
                spl_autoload_register(['Ddm', '_loadClass']);
                if (self::isInstalled()) {
                    //ini_set('display_errors','Off');
                    //ini_set('log_errors','Off');
                    set_error_handler(['Ddm', '_errorHandler'], E_ALL);
                    register_shutdown_function(['Ddm', '_onShutdown']);
                }
                self::$_config = new Config();
                self::$_language = new Language();
                if (!self::isInstalled() && !isset($_SERVER['CMS_INSTALL'])) {
                    Ddm\Request::singleton()->redirect(self::getCurrentBaseUrl() . 'install/');
                    return;
                } elseif (!isset($_SERVER['CMS_INSTALL'])) {
                    $install = new Core\Model\Install();
                    $install->applyInstall();
                }
                if (self::isInstalled()) {
                    self::$_language->isSaveToCookie(true)->loadFromVisitor($languageId);
                }
                self::$_translate = self::getHelper('Language');
                self::$_initialized = true;
            } catch (Exception $exception) {
                $exceptionLog = $exception->getMessage() . PHP_EOL . $exception->getTraceAsString();
                Log::saveTo('exception.log', $exceptionLog . PHP_EOL . 'URL: ' . Log::getCurrentUrl(true));
                if (self::$enableDebug) {
                    echo "<pre>{$exceptionLog}</pre>";
                }
            }
        }
    }

    /**
     * 是否是已经安装好了的
     *
     * @param bool $refresh
     * @return bool
     */
    public static function isInstalled(bool $refresh = false): bool
    {
        return self::$_isInstalled === null || $refresh ? (self::$_isInstalled = file_exists(SITE_ROOT . Config::XML_CONFIG_FILE)) : self::$_isInstalled;
    }

    private static function _loadClass(string $className): void
    {
        $classFile = str_replace('\\', DIRECTORY_SEPARATOR, ltrim($className, '\\')) . '.php';
        require $classFile;
    }

    /**
     * @access private
     */
    public static function _errorHandler(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        if (($errno & error_reporting()) == 0) {
            return false;
        }
        if ($errno == E_DEPRECATED) {
            // ignore strict and deprecated notices
            return true;
        }
        $errorMessage = '';
        switch ($errno) {
            case E_ERROR:
                $errorMessage .= 'Fatal Error';
                break;
            case E_WARNING:
                $errorMessage .= 'Warning';
                break;
            case E_PARSE:
                $errorMessage .= 'Parse Error';
                break;
            case E_NOTICE:
                $errorMessage .= 'Notice';
                break;
            case E_CORE_ERROR:
                $errorMessage .= 'Core Error';
                break;
            case E_CORE_WARNING:
                $errorMessage .= 'Core Warning';
                break;
            case E_COMPILE_ERROR:
                $errorMessage .= 'Compile Error';
                break;
            case E_COMPILE_WARNING:
                $errorMessage .= 'Compile Warning';
                break;
            case E_USER_ERROR:
                $errorMessage .= 'User Error';
                break;
            case E_USER_WARNING:
                $errorMessage .= 'User Warning';
                break;
            case E_USER_NOTICE:
                $errorMessage .= 'User Notice';
                break;
            case E_RECOVERABLE_ERROR:
                $errorMessage .= 'Recoverable Error';
                break;
            case E_DEPRECATED:
                $errorMessage .= 'Deprecated functionality';
                break;
            case E_USER_DEPRECATED:
                $errorMessage .= 'User-generated warning message';
                break;
            default:
                $errorMessage .= 'Unknown error ($errno)';
        }
        $errorMessage .= ": {$errstr} in {$errfile} on line {$errline}";
        Log::saveTo('php-errors.log', $errorMessage . PHP_EOL . 'URL: ' . Log::getCurrentUrl(true));

        if (self::$enableDebug) {
            echo "{$errorMessage}<br />\r\n";
        }
        return true;
    }

    /**
     * @access private
     */
    public static function _onShutdown(): void
    {
        if (Ddm\Db::isBeginTransaction()) {
            Ddm\Db::getWriteConn()->rollBack();
        }
        if ($error = error_get_last()) {
            self::_errorHandler($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }
}

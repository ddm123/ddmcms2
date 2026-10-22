<?php

namespace Core\Block;

use Cms\Model\Widget;
use Ddm;
use Ddm\Cache;
use Ddm\Controller;
use Ddm\Db;
use Ddm\Request;
use Ddm\StringHelper;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Template
{

    private array $_blocks = [];

    private array $_js = [];

    private array $_css = [];

    private array $_meta = [];

    private bool $_isInAdmin = false;

    protected string $_javascript = '';

    protected string $_style = '';

    protected bool $_blocksLock = false;

    protected mixed $_moduleName = null;

    protected mixed $_theme = null;

    protected array $_widgets = [];

    protected mixed $_designConfig = null;

    private static array $_templateFile = [];

    protected static mixed $_instance = null;

    protected static bool $_isInitTheme = false;

    public string $title = '';

    public string $template = '';

    public function __construct(mixed $inAdmin = IN_ADMIN)
    {
        $this->template = 'main-page.phtml';
        $this->_isInAdmin = (bool) $inAdmin;
        $this->addJavascript('var BASE_URL = "' . Ddm::getCurrentBaseUrl() . '";');
        $this->_initTheme();
        $this->_construct();
    }

    /**
     * 使用单例模式
     * @return \Core\Block\Template
     */
    public static function singleton(): Template
    {
        return self::$_instance === null ? self::$_instance = new Template() : self::$_instance;
    }

    /**
     * @return bool
     */
    public function isInAdmin(): bool
    {
        return $this->_isInAdmin;
    }

    /**
     * @param string $moduleName
     * @param string $blockClass
     * @param string $templateName
     * @return \Core\Block\AbstractModel
     */
    public function createBlock(string $moduleName, string $blockClass, ?string $templateName = null): AbstractModel
    {
        $blockClassName = '\\' . ucfirst($moduleName) . '\Block\\' . str_replace(' ', '\\', ucwords(str_replace('_', ' ', $blockClass)));
        $block = new $blockClassName();
        $block->setTemplateObject($this)->setModuleName($moduleName)->init();
        $block->setTemplate($templateName);
        return $block;
    }

    /**
     * 返回模板/图片/样式/Javascript文件的完整路径
     * @param string $file
     * @param string $type
     * @param string $moduleName
     * @param \Core\Block\Template $templateObj
     * @return string
     */
    public static function getTemplateFile(string $file, string $type = 'template', ?string $moduleName = null, ?Template $templateObj = null): string
    {
        if (!isset(self::$_templateFile["{$type}/{$file}/{$moduleName}"])) {
            $type == 'image' and $type = 'images';
            $moduleName = ucfirst($moduleName ? $moduleName : Controller::singleton()->getModuleName());
            $templateObj or $templateObj = self::singleton();
            $paths = [];
            $theme = $templateObj->getTheme();
            $folder = $templateObj->isInAdmin() ? 'adminhtml' : 'frontend';
            if ($theme != 'default') {
                //在主题文件夹找, default主题必须放在各自的模块里, 不可以放在公共的主题文件夹
                $paths[] = DESIGN_FOLDER . "/{$folder}/{$theme}/{$type}/{$moduleName}/{$file}";
                $paths[] = DESIGN_FOLDER . "/{$folder}/{$theme}/{$type}/{$file}";
            }
            $paths[] = MODULES_FOLDER . "/{$moduleName}/design/{$folder}/{$theme}/{$type}/{$file}";
            if ($moduleName != 'Core') {
                $paths[] = MODULES_FOLDER . "/Core/design/{$folder}/{$theme}/{$type}/{$file}";
            }
            //在系统模块的当前主题找
            if ($theme != 'default') {
                $paths[] = MODULES_FOLDER . "/{$moduleName}/design/{$folder}/default/{$type}/{$file}";
                //在当前模块的默认主题找
                if ($moduleName != 'Core') {
                    $paths[] = MODULES_FOLDER . "/Core/design/{$folder}/default/{$type}/{$file}";
                }
                //在核心模块的默认主题找
            }
            //echo '<pre>';print_r($paths);echo '</pre>';
            $pathString = false;
            foreach ($paths as $path) {
                if (is_file(SITE_ROOT . "/{$path}")) {
                    $pathString = $path;
                    break;
                }
            }
            if ($type == 'template') {
                self::$_templateFile["{$type}/{$file}/{$moduleName}"] = $pathString ? SITE_ROOT . "/{$pathString}" : false;
            } else {
                self::$_templateFile["{$type}/{$file}/{$moduleName}"] = Ddm::getLanguage()->getBaseUrl($type) . ($pathString ? $pathString : $paths[0]);
            }
        }
        return self::$_templateFile["{$type}/{$file}/{$moduleName}"];
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setModuleName(string $name): static
    {
        $this->_moduleName = $name;
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
     * @param string $template
     * @return $this
     */
    public function setTemplate(string $template): static
    {
        if ($template != '') {
            $this->template = $template;
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getTheme(): string
    {
        if ($this->_theme === null) {
            $this->_theme = Ddm::getConfig()->getConfigValue('web/design/' . ($this->_isInAdmin ? 'admin' : 'frontend') . '_theme') or $this->_theme = 'default';
            Ddm::dispatchEvent('get_theme_after', ['template' => $this]);
        }
        return $this->_theme;
    }

    /**
     * @param string $theme
     * @return $this
     */
    public function setTheme(string $theme): static
    {
        if ($theme) {
            $this->_theme = $theme;
        }
        return $this;
    }

    /**
     * @param string $file
     * @param string $type
     * @param string $moduleName
     * @return string
     */
    public function getFile(string $file, string $type = 'template', ?string $moduleName = null): string
    {
        return self::getTemplateFile($file, $type, $moduleName, $this);
    }

    /**
     * @param bool $lock
     * @return $this
     */
    public function setBlocksLock(bool $lock): static
    {
        $this->_blocksLock = $lock;
        return $this;
    }

    /**
     * @param \Core\Block\AbstractModel $block
     * @param string $name
     * @param bool $graceful 如果已存在相同名称的块, 是否抛出异常
     * @return $this
     */
    public function addBlock(AbstractModel $block, string $name, bool $graceful = false): static
    {
        if ($this->_blocksLock == false) {
            if (isset($this->_blocks[$name])) {
                if ($graceful == false) {
                    throw new \Exception($name . ' block name already exists');
                }
            } else {
                $this->_blocks[$name] = $block->setTemplateObject($this);
            }
        }
        return $this;
    }

    /**
     * 和$this->addBlock()的区别是该方法会插入到$this->_blocks数组的前面, 而不是未尾
     * @param \Core\Block\AbstractModel $block
     * @param string $name
     * @param bool $graceful 如果已存在相同名称的块, 是否抛出异常
     * @return $this
     */
    public function addBlockToFirst(AbstractModel $block, string $name, bool $graceful = false): static
    {
        if ($this->_blocksLock == false) {
            if (isset($this->_blocks[$name])) {
                if ($graceful == false) {
                    throw new \Exception($name . ' block name already exists');
                }
            } else {
                $this->_blocks = array_merge([$name => $block], $this->_blocks);
            }
        }
        return $this;
    }

    /**
     * @param string $name
     * @return \Core\Block\AbstractModel|array|false
     */
    public function getBlock(?string $name = null): AbstractModel|array|false
    {
        return $name === null ? $this->_blocks : (isset($this->_blocks[$name]) ? $this->_blocks[$name] : false);
    }

    /**
     * @param string $name
     * @return string
     */
    public function getBlockHtml(string $name): string
    {
        return isset($this->_blocks[$name]) ? $this->_blocks[$name]->toHtml() : '';
    }

    /**
     * @param string $string
     * @return $this
     */
    public function setTitle(string $string): static
    {
        $this->title = $string;
        return $this;
    }

    /**
     * 和setTitle()方法的区别是不断的拼接
     * @param string $string
     * @return \Core\Block\Template
     */
    public function addTitle(string $string): Template
    {
        $this->title = "{$string} - {$this->title}";
        return $this;
    }

    /**
     * @param string $nameValue
     * @param string $content
     * @param string $metaName
     * @param bool $isReplace 是否替换原来的
     * @return \Core\Block\Template
     */
    public function addMeta(string $nameValue, string $content, string $metaName = 'name', bool $isReplace = true): Template
    {
        if (!isset($this->_meta["{$metaName}={$nameValue}"]) || $isReplace) {
            $this->_meta["{$metaName}={$nameValue}"] = '<meta ' . $metaName . '="' . $nameValue . '" content="' . StringHelper::singleton()->escapeHtml($content) . '" />';
        }
        return $this;
    }

    /**
     * @param string $file
     * @param string $moduleName
     * @return \Core\Block\Template
     */
    public function addJs(string $file, ?string $moduleName = null): Template
    {
        isset($this->_js["{$file}:{$moduleName}"]) or $this->_js["{$file}:{$moduleName}"] = strpos($file, 'http') === 0 ? $file : self::getTemplateFile($file, 'js', $moduleName, $this);
        return $this;
    }

    /**
     * @param string $file
     * @param string $moduleName
     * @return \Core\Block\Template
     */
    public function addCss(string $file, ?string $moduleName = null): Template
    {
        isset($this->_css["{$file}:{$moduleName}"]) or $this->_css["{$file}:{$moduleName}"] = strpos($file, 'http') === 0 ? $file : self::getTemplateFile($file, 'css', $moduleName, $this);
        return $this;
    }

    /**
     * @param bool $asHtml
     * @return string|array
     */
    public function getMeta(bool $asHtml = false): string|array
    {
        return $asHtml ? implode("\r\n", $this->_meta) . "\r\n" : $this->_meta;
    }

    /**
     * @param bool $asHtml
     * @return string|array
     */
    public function getJs(bool $asHtml = false): string|array
    {
        if ($asHtml) {
            $html = '';
            foreach ($this->_js as $jsFile) {
                $html .= '<script src="' . $jsFile . '" type="text/javascript"></script>' . "\r\n";
            }
            return $html;
        }
        return $this->_js;
    }

    /**
     * @param bool $asHtml
     * @return string|array
     */
    public function getCss(bool $asHtml = false): string|array
    {
        if ($asHtml) {
            $html = '';
            foreach ($this->_css as $cssFile) {
                $html .= '<link href="' . $cssFile . '" rel="stylesheet" type="text/css" />' . "\r\n";
            }
            return $html;
        }
        return $this->_css;
    }

    /**
     * @param string $javascript
     * @return $this
     */
    public function addJavascript(string $javascript): static
    {
        $this->_javascript == '' or $this->_javascript .= "\r\n";
        $this->_javascript .= $javascript;
        return $this;
    }

    /**
     * @return string
     */
    public function getJavascript(): string
    {
        return $this->_javascript;
    }

    /**
     * @param string $style
     * @return $this
     */
    public function addStyle(string $style): static
    {
        if ($style) {
            $style = trim(preg_replace('/(\{|\}|;|\:)\s+/', '\1', $style));
            $this->_style .= $style;
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getStyle(): string
    {
        return $this->_style;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function removeBlock(string $name): static
    {
        if (isset($this->_blocks[$name])) {
            unset($this->_blocks[$name]);
        }
        return $this;
    }

    /**
     * @param string $file
     * @param string $moduleName
     * @return \Core\Block\Template
     */
    public function removeCss(string $file, ?string $moduleName = null): Template
    {
        if (isset($this->_css["{$file}:{$moduleName}"])) {
            unset($this->_css["{$file}:{$moduleName}"]);
        }
        return $this;
    }

    /**
     * @param string $file
     * @param string $moduleName
     * @return \Core\Block\Template
     */
    public function removeJs(string $file, ?string $moduleName = null): Template
    {
        if (isset($this->_js["{$file}:{$moduleName}"])) {
            unset($this->_js["{$file}:{$moduleName}"]);
        }
        return $this;
    }

    /**
     * @param string $nameValue
     * @param string $metaName
     * @return \Core\Block\Template
     */
    public function removeMeta(string $nameValue, string $metaName = 'name'): Template
    {
        if (isset($this->_meta["{$metaName}={$nameValue}"])) {
            unset($this->_meta["{$metaName}={$nameValue}"]);
        }
        return $this;
    }

    /**
     * @param string $identifier
     * @return Widget
     */
    public function getWidget(string $identifier): Widget
    {
        if (!isset($this->_widgets[$identifier])) {
            $this->_widgets[$identifier] = $this->createBlock('cms', 'widget_view')->setTemplateObject($this)->init()->getWidget($identifier);
        }
        return $this->_widgets[$identifier];
    }

    /**
     * @param string $identifier
     * @return string
     */
    public function getWidgetHtml(string $identifier): string
    {
        return $this->getWidget($identifier)->content;
    }

    /**
     * 获取单页面的链接地址
     * @param string $url
     * @return string
     */
    public function getOnepageUrl(string $url): string
    {
        return Ddm::getLanguage()->getHomeUrl() . $url;
    }

    /**
     * @param string $path
     * @param int $languageId
     * @return string|null
     */
    public function getConfig(string $path, ?int $languageId = null): ?string
    {
        return Ddm::getConfig()->getConfigValue($path, $languageId);
    }

    /**
     * @param string $type
     * @return string
     */
    public function getBaseUrl(string $type = 'link'): string
    {
        return Ddm::getLanguage()->getBaseUrl($type);
    }

    /**
     * @return array
     */
    public function getDesignConfig(): array
    {
        if ($this->_designConfig === null) {
            $configFile = SITE_ROOT . '/' . DESIGN_FOLDER . '/' . ($this->isInAdmin() ? 'adminhtml' : 'frontend') . '/' . $this->getTheme() . '/config.xml';
            if ($this->getTheme() == 'default' || !is_file($configFile)) {
                $this->_designConfig = [];
            } else {
                $cacheKey = 'template_design_config_' . ($this->isInAdmin() ? 'adminhtml' : 'frontend') . '_' . $this->getTheme();
                $this->_designConfig = Cache::load($cacheKey);
                if ($this->_designConfig === false) {
                    $this->_designConfig = Ddm::getConfig()->xmlFileToArray($configFile) or $this->_designConfig = [];
                    Cache::save($cacheKey, $this->_designConfig, ['config'], 0);
                }
            }
        }
        return $this->_designConfig;
    }

    /**
     * @return string
     */
    public function fetch(): string
    {
        $html = '';
        Ddm::dispatchEvent('template_fetch_before', ['object' => $this]);
        $this->_beforeFetch();
        foreach ($this->_blocks as $name => $block) {
            //if($block->template==='' && preg_match('/^[\w\-\.]+$/',$name))$block->template = "$name.phtml";
            if ($block->isFetch()) {
                $html .= $block->toHtml();
            }
        }
        Ddm::dispatchEvent('template_fetch_after', ['object' => $this]);
        $this->_afterFetch();
        return $html;
    }

    /**
     * @param string $moduleName
     * @return $this
     */
    public function display(?string $moduleName = null): static
    {
        Ddm::dispatchEvent('template_display_before', ['object' => $this]);
        $this->_beforeDisplay();
        if ($this->template) {
            $templateFile = self::getTemplateFile($this->template, 'template', $moduleName ? $moduleName : $this->_moduleName, $this);
            if ($templateFile) {
                include $templateFile;
            } else {
                echo 'Template file(' . $this->template . ') not found';
            }
        }
        Ddm::dispatchEvent('template_display_after', ['object' => $this]);
        $this->_afterDisplay();
        return $this;
    }

    /**
     * @return $this
     */
    public function clear(): static
    {
        $this->_blocks = [];
        $this->_css = [];
        $this->_js = [];
        return $this;
    }

    /**
     * @return float
     */
    public function getRunTime(): float
    {
        return round(microtime(true) - Request::server()->START_TIME, 4);
    }

    /**
     * @return int
     */
    public function getQueryCount(): int
    {
        $_count = 0;
        $databaseConfig = Ddm::getConfig()->getDbConfig();
        $connections = isset($databaseConfig['driver']) && is_string($databaseConfig['driver']) ? Db::getAllConnections() ? [current(Db::getAllConnections())] : [] : Db::getAllConnections();
        foreach ($connections as $conn) {
            $_count += $conn->getQueryCount();
        }
        return $_count;
    }

    /**
     * @return string
     */
    public function getMemoryUsage(): string
    {
        return StringHelper::singleton()->formatBytes(memory_get_usage());
    }

    /**
     * @return $this
     */
    protected function _construct(): static
    {
        $this->addMeta('author', 'DDM')->addCss('common.css', 'core');
        $this->addBlock($this->createBlock('core', 'head')->isFetch(false), 'head');
        return $this;
    }

    /**
     * @return $this
     */
    protected function _initTheme(): static
    {
        if (!self::$_isInitTheme and $themeFile = $this->_getThemeFile()) {
            $theme = include $themeFile;
            if (is_string($theme)) {
                $theme = new $theme();
            } elseif (!is_object($theme)) {
                return $this;
            }
            if (method_exists($theme, 'init')) {
                $theme->init($this);
            }
            self::$_isInitTheme = true;
        }
        return $this;
    }

    /**
     * @return string|false
     */
    protected function _getThemeFile(): string|false
    {
        $fileName = 'theme.php';
        $paths = [];
        $theme = $this->getTheme();
        $folder = $this->isInAdmin() ? 'adminhtml' : 'frontend';
        if ($theme != 'default') {
            $paths[] = DESIGN_FOLDER . "/{$folder}/{$theme}/{$fileName}";
        }
        $paths[] = DESIGN_FOLDER . "/{$folder}/default/{$fileName}";
        //在系统模块的当前主题找
        if ($theme != 'default') {
            $paths[] = MODULES_FOLDER . "/Core/design/{$folder}/{$theme}/{$fileName}";
        }
        $paths[] = MODULES_FOLDER . "/Core/design/{$folder}/default/{$fileName}";
        foreach ($paths as $path) {
            if (is_file(SITE_ROOT . DIRECTORY_SEPARATOR . $path)) {
                return SITE_ROOT . DIRECTORY_SEPARATOR . $path;
            }
        }
        return false;
    }

    /**
     * @return $this
     */
    protected function _beforeFetch(): static
    {
        return $this;
    }

    /**
     * @return $this
     */
    protected function _afterFetch(): static
    {
        return $this;
    }

    /**
     * @return $this
     */
    protected function _beforeDisplay(): static
    {
        if ($this->getDesignConfig() && !empty($this->_designConfig['methods'])) {
            foreach ($this->_designConfig['methods'] as $method => $params) {
                if (isset($params[0])) {
                    foreach ($params as $_params) {
                        call_user_func_array([$this, $method], $_params);
                    }
                } else {
                    call_user_func_array([$this, $method], $params);
                }
            }
        }
        return $this;
    }

    /**
     * @return $this
     */
    protected function _afterDisplay(): static
    {
        return $this;
    }
}

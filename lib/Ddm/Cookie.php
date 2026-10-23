<?php

namespace Ddm;

use Ddm;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Cookie
{

    public const CONFIG_PATH_COOKIE_DOMAIN = 'web/cookie/cookie_domain';

    public const CONFIG_PATH_COOKIE_PATH = 'web/cookie/cookie_path';

    public const CONFIG_PATH_COOKIE_LIFETIME = 'web/cookie/cookie_lifetime';

    public const CONFIG_PATH_COOKIE_HTTPONLY = 'web/cookie/cookie_httponly';

    public const CONFIG_PATH_COOKIE_PREFIX = 'web/cookie/cookie_prefix';

    protected static mixed $_instance = null;

    protected ?array $_lifetime = null;

    protected ?array $_path = null;

    protected ?array $_domain = null;

    protected ?bool $_isSecure = null;

    protected ?array $_httponly = null;

    protected string $_prefix = '';

    public function __construct()
    {
        $this->_prefix = (string) Ddm::getConfig()->getConfigValue(self::CONFIG_PATH_COOKIE_PREFIX);
    }

    /**
     * 使用单例模式
     *
     * @return \Ddm\Cookie
     */
    public static function singleton(): Cookie
    {
        return self::$_instance === null ? self::$_instance = new Cookie() : self::$_instance;
    }

    /**
     * Retrieve Domain for cookie
     * @return string|null
     */
    public function getDomain(): ?string
    {
        if ($this->_domain === null) {
            $this->_domain = ['value' => Ddm::getConfig()->getConfigValue(self::CONFIG_PATH_COOKIE_DOMAIN)];
        }
        return $this->_domain['value'];
    }

    /**
     * Set cookie domain
     * @param string $domain
     * @return $this
     */
    public function setDomain(string $domain): static
    {
        if ($this->_domain) {
            $this->_domain['value'] = $domain;
        } else {
            $this->_domain = ['value' => $domain];
        }
        return $this;
    }

    /**
     * Retrieve Path for cookie
     * @return string|null
     */
    public function getPath(): ?string
    {
        if ($this->_path === null) {
            $this->_path = ['value' => Ddm::getConfig()->getConfigValue(self::CONFIG_PATH_COOKIE_PATH)];
        }
        return $this->_path['value'];
    }

    /**
     * Set cookie path
     * @param string $path
     * @return $this
     */
    public function setPath(string $path): static
    {
        if ($this->_path) {
            $this->_path['value'] = $path;
        } else {
            $this->_path = ['value' => $path];
        }
        return $this;
    }

    /**
     * Retrieve cookie lifetime
     * @return int|null
     */
    public function getLifetime(): ?int
    {
        if ($this->_lifetime === null) {
            $lifetime = Ddm::getConfig()->getConfigValue(self::CONFIG_PATH_COOKIE_LIFETIME);
            if ($lifetime === '' || $lifetime === null) {
                $lifetime = null;
            } else {
                $lifetime = (int) $lifetime * 60;
            }
            $this->_lifetime = ['value' => $lifetime];
        }
        return $this->_lifetime['value'];
    }

    /**
     * Set cookie lifetime
     *
     * @param int $lifetime seconds
     * @return $this
     */
    public function setLifetime(int $lifetime): static
    {
        if ($this->_lifetime) {
            $this->_lifetime['value'] = $lifetime;
        } else {
            $this->_lifetime = ['value' => $lifetime];
        }
        return $this;
    }

    /**
     * Retrieve use HTTP only flag
     *
     * @return bool|null
     */
    public function getHttponly(): ?bool
    {
        if ($this->_httponly === null) {
            $httponly = Ddm::getConfig()->getConfigValue(self::CONFIG_PATH_COOKIE_HTTPONLY);
            if ($httponly === '' || $httponly === null) {
                $httponly = null;
            } else {
                $httponly = (bool) $httponly;
            }
            $this->_httponly = ['value' => $httponly];
        }
        return $this->_httponly['value'];
    }

    /**
     * Set cookie httponly
     *
     * @param bool $httponly
     * @return $this
     */
    public function setHttponly(bool $httponly): static
    {
        if ($this->_httponly) {
            $this->_httponly['value'] = $httponly;
        } else {
            $this->_httponly = ['value' => $httponly];
        }
        return $this;
    }

    /**
     * Is https secure request
     * Use secure on adminhtml only
     *
     * @return bool|null
     */
    public function isSecure(): ?bool
    {
        return $this->_isSecure;
    }

    /**
     * Set cookie secure
     *
     * @param bool $secure
     * @return $this
     */
    public function setSecure(bool $secure): static
    {
        $this->_isSecure = $secure === null ? null : (bool) $secure;
        return $this;
    }

    /**
     * Retrieve cookie or NULL if not exists
     *
     * @param string $name The cookie name
     * @return mixed
     */
    public function get(string $name): mixed
    {
        return isset($_COOKIE[$this->_prefix . $name]) ? $_COOKIE[$this->_prefix . $name] : null;
    }

    /**
     * Set cookie
     *
     * @param string $name The cookie name
     * @param string $value The cookie value
     * @param int $lifetime
     * @param string $path
     * @param string $domain
     * @param int|bool $secure
     * @param bool $httponly
     * @param string $prefix
     * @return $this
     */
    public function set(string $name, string $value, ?int $lifetime = null, ?string $path = null, ?string $domain = null, int|bool|null $secure = null, ?bool $httponly = null, ?string $prefix = null): static
    {
        $name = ($prefix === null ? $this->_prefix : $prefix) . $name;
        $lifetime === null and $lifetime = $this->getLifetime();
        $expire = $lifetime ? $lifetime + Request::server()->REQUEST_TIME : 0;
        $path === null and $path = $this->getPath();
        $domain === null and $domain = $this->getDomain();
        $secure === null and $secure = $this->isSecure();
        $httponly === null and $httponly = $this->getHttponly();
        setcookie($name, $value, $expire, (string) $path, (string) $domain, (bool) $secure, (bool) $httponly);
        $_COOKIE[$name] = $value;
        return $this;
    }

    /**
     * Delete cookie
     *
     * @param string $name
     * @param string $path
     * @param string $domain
     * @param int|bool $secure
     * @param int|bool $httponly
     * @return $this
     */
    public function delete(string $name, ?string $path = null, ?string $domain = null, int|bool|null $secure = null, int|bool|null $httponly = null): static
    {
        $name = $this->_prefix . $name;
        $path === null and $path = $this->getPath();
        $domain === null and $domain = $this->getDomain();
        $secure === null and $secure = $this->isSecure();
        $httponly === null and $httponly = $this->getHttponly();
        setcookie($name, '', Request::server()->REQUEST_TIME - 3600, (string) $path, (string) $domain, (bool) $secure, (bool) $httponly);
        unset($_COOKIE[$name]);
        return $this;
    }
}

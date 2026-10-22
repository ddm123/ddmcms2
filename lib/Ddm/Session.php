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
class Session extends DataObject
{

    protected static mixed $_instance = null;

    protected mixed $_sessionSaveHandler = null;

    public function __construct()
    {
        //没有调用父类的构造函数
    }

    public function __destruct()
    {
        $this->toArray();
        session_write_close();
    }

    /**
     * @return string
     */
    protected function _getSessionSavePath(): string
    {
        $path = SITE_ROOT . '/data/session';
        is_dir($path) or mkdir($path, 0755, true);
        return $path;
    }

    /**
     * 使用单例模式
     * @return \Ddm\Session
     */
    public static function singleton(): Session
    {
        if (self::$_instance === null) {
            self::$_instance = new Session();
            self::$_instance->init();
        }
        return self::$_instance;
    }

    /**
     * @param string $sessionName
     * @return $this
     */
    public function start(?string $sessionName = null): static
    {
        if (isset($_SESSION)) {
            return $this;
        }
        $handler = $this->getSessionSaveHandler();
        if ($handler == 'files') {
            session_save_path($this->_getSessionSavePath());
        } else {
            $handler = $this->getSessionSaveHandler();
            $handlerObject = new $handler();
            $handlerObject->setSaveHandler();
        }
        $cookieLifetime = Cookie::singleton()->getLifetime();
        session_set_cookie_params((int) $cookieLifetime, Cookie::singleton()->getPath(), Cookie::singleton()->getDomain(), Cookie::singleton()->isSecure(), Cookie::singleton()->getHttponly());
        $this->setSessionName($sessionName)->setSessionId();
        session_start();
        $cookieLifetime and Cookie::singleton()->set(session_name(), session_id(), $cookieLifetime, Cookie::singleton()->getPath(), Cookie::singleton()->getDomain(), Cookie::singleton()->isSecure(), Cookie::singleton()->getHttponly(), '');
        return $this;
    }

    /**
     * @param string $namespace
     * @param string $sessionName
     * @return $this
     */
    public function init(?string $namespace = null, ?string $sessionName = null): static
    {
        $namespace or $namespace = 'core';
        $sessionName or $sessionName = 'accessid';
        $this->start($sessionName);
        isset($_SESSION[$namespace]) or $_SESSION[$namespace] = [];
        $this->_data = & $_SESSION[$namespace];
        return $this;
    }

    /**
     * @return string
     */
    public function getSessionSaveHandler(): string
    {
        if ($this->_sessionSaveHandler === null) {
            $this->_sessionSaveHandler = 'files';
            if (Ddm::isInstalled() && Ddm::getConfig()->getConfigValue('session/save_handler')) {
                $this->_sessionSaveHandler = Ddm::getConfig()->getConfigValue('session/save_handler');
            }
        }
        return $this->_sessionSaveHandler;
    }

    /**
     * @param string $sessionName
     * @return $this
     */
    public function setSessionName(string $sessionName): static
    {
        if ($sessionName) {
            $prefix = '';
            if (Ddm::isInstalled()) {
                $prefix = Ddm::getConfig()->getConfigValue(Cookie::CONFIG_PATH_COOKIE_PREFIX);
            }
            session_name($prefix . $sessionName);
        }
        return $this;
    }

    /**
     * @param string $sessionId
     * @return $this
     */
    public function setSessionId(?string $sessionId = null): static
    {
        if ($sessionId === null && isset($_GET[$name = session_name()])) {
            $sessionId = $_GET[$name];
        }
        if ($sessionId && preg_match('/^[0-9a-zA-Z,-]+$/', $sessionId)) {
            session_id($sessionId);
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getSessionId(): string
    {
        return session_id();
    }
}

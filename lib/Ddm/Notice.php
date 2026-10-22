<?php

namespace Ddm;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Notice
{

    protected string $_cookieVarname = 'notice';

    protected int $_cookieLifetime = 600;

    protected array $_notices = [];

    /**
     * @param bool $getCookie
     */
    public function __construct(bool $getCookie = true)
    {
        if ($getCookie) {
            $this->_getNoticesFromCookie();
        }
    }

    /**
     * @param string $key
     * @param string $str
     * @return $this
     */
    protected function _addNotices(string $key, string $str): static
    {
        if (isset($this->_notices[$key])) {
            $this->_notices[$key][] = $str;
        } else {
            $this->_notices[$key] = [$str];
        }
        return $this;
    }

    /**
     * @return array
     */
    protected function _getNoticesFromCookie(): array
    {
        $notices = Cookie::singleton()->get($this->_cookieVarname);
        if ($notices) {
            Cookie::singleton()->delete($this->_cookieVarname, null, null, null, true);
            if ($notices = unserialize(StringHelper::singleton()->base64Decode($notices))) {
                $this->_notices = $notices;
            }
        }
        return $this->_notices;
    }

    /**
     * @return array
     */
    public function getNotices(): array
    {
        return $this->_notices;
    }

    /**
     * @param string $str
     * @return $this
     */
    public function addNotice(string $str): static
    {
        $this->_addNotices('notices', $str);
        return $this;
    }

    /**
     * @param string $str
     * @return $this
     */
    public function addSuccess(string $str): static
    {
        $this->_addNotices('success', $str);
        return $this;
    }

    /**
     * @param string $str
     * @return $this
     */
    public function addError(string $str): static
    {
        $this->_addNotices('errors', $str);
        return $this;
    }

    /**
     * @param string $str
     * @return $this
     */
    public function addWarning(string $str): static
    {
        $this->_addNotices('warnings', $str);
        return $this;
    }

    /**
     * @param string $str
     * @return $this
     */
    public function save(): static
    {
        if ($this->_notices && !headers_sent()) {
            $notices = StringHelper::singleton()->base64Encode(serialize($this->_notices));
            Cookie::singleton()->set($this->_cookieVarname, $notices, $this->_cookieLifetime, null, null, null, true);
            $this->_notices = [];
        }
        return $this;
    }

    /**
     * @param array $notices
     * @return string
     */
    public function toHtml(?array $notices = null): string
    {
        $notices or $notices = $this->_notices;
        $_html = '';
        if ($notices) {
            $_html = '<ul class="notices__">';
            foreach ($notices as $key => $value) {
                $_html .= '<li class="' . $key . '"><ul>';
                foreach ($value as $msg) {
                    $_html .= '<li>' . $msg . '</li>';
                }
                $_html .= '</ul></li>';
            }
            $_html .= '</ul>';
        }
        return $_html;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->toHtml();
    }
}

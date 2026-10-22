<?php

namespace Admin\Model;

use Core\Model\AbstractModel;
use Ddm;
use Ddm\Cookie;
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
/**
 * @method \Admin\Model\Resource\Admin getResource()
 * @property string $admin_name
 * @property int $loginnum
 * @property int $is_active
 * @property int $edit_name
 * @property int $edit_pass
 */
class Admin extends AbstractModel
{

    public const COOKIE_VARNAME = 'admin_c';

    public const SESSION_VARNAME = 'loggedin_admin';

    protected static mixed $_instance = null;

    protected static mixed $_loggedInAdmin = null;

    protected mixed $_allows = null;

    protected string $_loggedStatusAdapter = 'session';
    //cookie or session

    private mixed $_isLoggedIn = null;

    private mixed $_verifyCookieResult = null;

    private mixed $_verifySessionResult = null;

    /**
     * 使用单例模式
     * @return \Admin\Model\Admin
     */
    public static function singleton(): Admin
    {
        return self::$_instance === null ? self::$_instance = new Admin() : self::$_instance;
    }

    /**
     * 获取当前登录的管理员
     * @return \Admin\Model\Admin
     */
    public static function loggedInAdmin(): Admin
    {
        return self::$_loggedInAdmin === null ? self::$_loggedInAdmin = new Admin() : self::$_loggedInAdmin;
    }

    /**
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        if ($this->_isLoggedIn === null) {
            $this->_isLoggedIn = $this->getLoggedStatusAdapter() == 'cookie' ? $this->verifyCookie() === 1 : $this->verifySession() === 1;
            if ($this->_isLoggedIn && $this->getLoggedStatusAdapter() == 'cookie' && Cookie::singleton()->getLifetime()) {
                $this->_saveLoggedInStatus();
                //刷新Cookie的过期时间
            }
        }
        return $this->_isLoggedIn;
    }

    /**
     * 返回：0用户名密码错误,1登录成功,2帐户被禁用
     * @param string $username
     * @param string $password
     * @return int
     */
    public function login(string $username, string $password): int
    {
        if ($username != '' && $password != '') {
            if ($this->getResource()->login($this, $username, $password)) {
                if ($this->is_active) {
                    $this->_afterSuccessfulLogin();
                    return 1;
                } else {
                    return 2;
                }
            }
        }
        return 0;
    }

    /**
     * @param int $id
     * @return int 0:ID不存在,1:登录成功,2:帐户被禁用
     */
    public function loginById(int $id): int
    {
        if ($id = (int) $id) {
            $this->load($id);
            if ($this->getId()) {
                if ($this->is_active) {
                    $this->_afterSuccessfulLogin();
                    return 1;
                } else {
                    return 2;
                }
            }
        }
        return 0;
    }

    /**
     * 返回一个整数, 0:不是有效的Session, 1:验证通过, 2:帐户已被其他人在另一设备登录
     * @return int
     */
    public function verifyCookie(): int
    {
        if ($this->_verifyCookieResult === null) {
            $this->_verifyCookieResult = 0;
            if ($cookieHash = Cookie::singleton()->get(self::COOKIE_VARNAME)) {
                $cookieHash = explode('-', StringHelper::singleton()->base64Decode($cookieHash), 3);
                if (isset($cookieHash[2]) && isset($cookieHash[1]) && is_numeric($cookieHash[1]) && is_numeric($cookieHash[0])) {
                    if (!$this->getId()) {
                        $this->load($cookieHash[0]);
                    }
                    if ($cookieHash2 = $this->getCookieHash(true)) {
                        if ($this->admin_id && $this->is_active && $cookieHash2[2] === $cookieHash[2]) {
                            $this->_verifyCookieResult = $cookieHash2[1] === $cookieHash[1] ? 1 : 2;
                        }
                    }
                }
            }
        }
        return $this->_verifyCookieResult;
    }

    /**
     * 返回一个整数, 0:不是有效的Session, 1:验证通过, 2:帐户已被其他人在另一设备登录
     * @return int
     */
    public function verifySession(): int
    {
        if ($this->_verifySessionResult === null) {
            $this->_verifySessionResult = 0;
            if ($adminData = Ddm::getHelper('admin')->getSession()->getData(self::SESSION_VARNAME)) {
                if (isset($adminData[2]) && isset($adminData[1]) && is_numeric($adminData[1]) && is_numeric($adminData[0])) {
                    if (!$this->getId()) {
                        $this->load($adminData[0]);
                    }
                    $cookieHash = $this->getCookieHash(true);
                    if (!$cookieHash || !$this->is_active || $cookieHash[2] != $adminData[2]) {
                        return $this->_verifySessionResult;
                    }
                    $this->_verifySessionResult = $cookieHash[1] == $adminData[1] ? 1 : 2;
                }
            }
        }
        return $this->_verifySessionResult;
    }

    /**
     * @return bool
     */
    public function logOut(): bool
    {
        $this->getLoggedStatusAdapter() == 'cookie' ? Cookie::singleton()->delete(self::COOKIE_VARNAME, null, null, null, true) : Ddm::getHelper('admin')->getSession()->unsetData(self::SESSION_VARNAME);
        $this->_isLoggedIn = false;
        return true;
    }

    /**
     * @return string
     */
    public function getLoggedStatusAdapter(): string
    {
        return $this->_loggedStatusAdapter;
    }

    /**
     * @param bool $asArray
     * @return string|array
     */
    public function getCookieHash(bool $asArray = false): string|array
    {
        $data = $this->getId() ? [$this->admin_id, $this->logintime, md5("{$this->admin_name}/{$this->admin_pass}/" . Ddm::getConfig()->getConfigValue('hash_key'))] : [];
        if ($asArray) {
            return $data;
        }
        return $data ? "{$data[0]}-{$data[1]}-{$data[2]}" : '';
    }

    /**
     * @return array
     */
    public function getGroups(): array
    {
        if ($this->groups === null) {
            if ($this->getId()) {
                $this->groups = $this->getResource()->getGroupsFromAdminId($this->getId());
            }
        }
        return $this->groups;
    }

    /**
     * @return array
     */
    public function getAllows(): array
    {
        if ($this->_allows === null) {
            $this->_allows = $this->getId() ? Group::singleton()->getAllowsFromAdminId($this->getId()) : [];
        }
        return $this->_allows;
    }

    /**
     * @param string $path
     * @return bool
     */
    public function isAllow(string $path, mixed $type = Group::ALLOW_TYPE_READ): bool
    {
        foreach ($this->getAllows() as $row) {
            if ($row == 'all') {
                return true;
            }
            $path = strtolower($path);
            if (isset($row[$path])) {
                if ($type == Group::ALLOW_TYPE_READ) {
                    foreach ($row[$path] as $allowValue) {
                        if ($allowValue) {
                            return true;
                        }
                    }
                    return false;
                } else {
                    return isset($row[$path][$type]) ? (bool) $row[$path][$type] : false;
                }
            }
        }
        return false;
    }

    /**
     * @param string $password
     * @return string
     */
    public function getPasswordHash(string $password): string
    {
        return md5("{$password}/" . base64_encode($password));
    }

    /**
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function setExtra(string $key, ?string $value = null): static
    {
        if (is_array($key)) {
            $this->extra = $key;
        } else {
            $this->extra = $this->getExtra();
            $this->extra[$key] = $value;
        }
        return $this;
    }

    /**
     * @param string $key
     * @return string|array
     */
    public function getExtra(string|bool $key = false): string|array
    {
        if ($this->extra) {
            if (is_string($this->extra)) {
                $this->extra = unserialize($this->extra) or $this->extra = [];
            }
            return $key === false ? $this->extra : (isset($this->extra[$key]) ? $this->extra[$key] : null);
        }
        return $key === false ? [] : null;
    }

    /**
     * @return $this
     */
    protected function _afterSuccessfulLogin(): static
    {
        if ($this->admin_id) {
            $this->addData(['loginnum' => $this->loginnum + 1, 'prevtime' => $this->logintime, 'logintime' => Request::server()->REQUEST_TIME])->save();
            $this->_isLoggedIn = true;
            $this->_saveLoggedInStatus();
        }
        return $this;
    }

    /**
     * @return $this
     */
    protected function _saveLoggedInStatus(): static
    {
        if ($this->isLoggedIn() === true) {
            if ($this->getLoggedStatusAdapter() == 'cookie') {
                Cookie::singleton()->set(self::COOKIE_VARNAME, StringHelper::singleton()->base64Encode($this->getCookieHash()), null, null, null, null, true);
            } else {
                Ddm::getHelper('admin')->getSession()->setData(self::SESSION_VARNAME, $this->getCookieHash(true));
            }
        }
        return $this;
    }

    protected function _beforeSave(): static
    {
        if ($groupsPosition = $this->groups_position) {
            $groupIds = Db::table('admin_group')->whereIn('group_id', array_keys($groupsPosition))->pluck('group_id');
            if ($groupIds) {
                foreach ($groupsPosition as $groupId => $position) {
                    if (!in_array($groupId, $groupIds)) {
                        unset($groupsPosition[$groupId]);
                    } else {
                        $groupsPosition[$groupId] = (int) $position;
                    }
                }
                $this->groups_position = $groupsPosition;
            } else {
                $this->unsetData('groups_position');
            }
        }
        if (!$this->getId() && !$this->groups_position) {
            throw new \Exception(Ddm::getTranslate('admin')->translate('至少要选择一个管理员组'));
        }
        if ($this->admin_pass) {
            if (is_string($this->admin_pass) && !preg_match('/^[0-9a-z]{32}$/', $this->admin_pass)) {
                $this->admin_pass = $this->getPasswordHash($this->admin_pass);
            }
        } else {
            $this->admin_pass = $this->getOrigData('admin_pass') or $this->unsetData('admin_pass');
        }
        if (!$this->getId() && !$this->logintime) {
            $this->logintime = Request::server()->REQUEST_TIME;
        }
        if (!$this->getId() && !$this->prevtime) {
            $this->prevtime = Request::server()->REQUEST_TIME;
        }
        return parent::_beforeSave();
    }

    protected function _afterSave(): static
    {
        if ($this->issetData('groups_position')) {
            Db::table('admin_group_user')->where('admin_id', '=', $this->getId())->delete();
        }
        if ($this->groups_position) {
            foreach ($this->groups_position as $groupId => $position) {
                Db::getWriteConn()->insert(Db::getTable('admin_group_user'), ['group_id' => $groupId, 'admin_id' => $this->getId(), 'position' => $position], false, ['position' => $position]);
            }
        }
        return parent::_afterSave();
    }

    protected function _afterDelete(): static
    {
        Db::table('admin_group_user')->where('admin_id', '=', $this->getId())->delete();
        return parent::_afterDelete();
    }

    protected function _afterLoad(): static
    {
        parent::_afterLoad();
        if ($this->extra != '') {
            $this->extra = $this->getExtra();
        }
        return $this;
    }
}

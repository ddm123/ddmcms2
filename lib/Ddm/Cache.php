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
class Cache
{

    protected static mixed $_instance = null;

    protected static bool $_registerShutdown = false;

    protected mixed $_cache = null;

    protected array $_isLocked = [];

    protected string $_prefix = '';

    protected string $_lockKey = 'LOCK_SAVE_';

    public function __construct()
    {
        $this->getCache();

        if (!self::$_registerShutdown) {
            self::$_registerShutdown = true;
            register_shutdown_function($this->_unlockAll(...));
        }
    }

    /**
     * 使用单例模式
     * @return \Ddm\Cache
     */
    public static function singleton(): Cache
    {
        return self::$_instance === null ? self::$_instance = new Cache() : self::$_instance;
    }

    /**
     * 保存存一条缓存数据
     * @param string $id
     * @param mixed $data
     * @param array $tags
     * @param int $lifetime 如果是0则永不过期
     * @return bool
     */
    public static function save(string $id, mixed $data, ?array $tags = null, int $lifetime = 0): bool
    {
        return self::singleton()->set($id, $data, $tags, $lifetime);
    }

    /**
     * 读出一条缓存数据
     * @param string $id
     * @return mixed
     */
    public static function load(string $id): mixed
    {
        return self::singleton()->get($id);
    }

    /**
     * 删除一条缓存数据
     * @param string $id
     * @return \Ddm\Cache
     */
    public static function remove(string $id): Cache
    {
        return self::singleton()->delete($id);
    }

    /**
     * 删除所有缓存数据
     * @return bool
     */
    public static function clear(): bool
    {
        return self::singleton()->getCache()->clear();
    }

    /**
     * @return \Ddm\Cache\InterfaceCache
     */
    public function getCache(): Cache\InterfaceCache
    {
        if ($this->_cache === null) {
            $config = Ddm::getConfig()->getXmlConfig();
            $cacheClass = !isset($config['cache']['driver']) || $config['cache']['driver'] == '' ? '\Ddm\Cache\File' : $config['cache']['driver'];
            $this->_cache = new $cacheClass(isset($config['cache']) ? $config['cache'] : []);
            $this->_prefix = isset($config['cache']['prefix']) ? $config['cache']['prefix'] : '';
        }
        return $this->_cache;
    }

    /**
     * @param string $id
     * @param mixed $data
     * @param array $tags
     * @param int $lifetime 如果是0则永不过期
     * @return bool
     */
    public function set(string $id, mixed $data, ?array $tags = null, int $lifetime = 0): bool
    {
        $id = $this->_id($id, $this->_prefix);
        $tags = $this->_tag($tags ? $tags : ['no_tag']);
        $this->_unlock($id);
        return $this->_cache->save($id, $this->_prepareData($data, $lifetime), $tags, $lifetime ? $lifetime + 100 : 0);
    }

    /**
     * @param string $id
     * @return mixed
     */
    public function get(string $id): mixed
    {
        $id = $this->_id($id, $this->_prefix);
        if (isset($this->_isLocked[$id]) && $this->_isLocked[$id]) {
            return false;
        }
        if ($result = $this->_cache->load($id) and $result = unserialize($result)) {
            $result = !$result['lifetime'] || $result['expire'] > Request::server()->REQUEST_TIME || $this->_isLocked($id) ? $result['data'] : false;
            $result === false and $this->_lock($id);
            //缓存已失效, 加锁, 只让一进程重新生成缓存, 其它进程继续使用原来的旧缓存
            return $result;
        }
        return false;
    }

    /**
     * @param string $id
     * @return $this
     */
    public function delete(string $id): static
    {
        $id = $this->_id($id, $this->_prefix);
        $this->_cache->remove($id);
        return $this;
    }

    /**
     * @param array $tags
     * @return array
     */
    public function getIdByTags(array $tags): array
    {
        $tags = $this->_tag($tags);
        return $this->_cache->getIdByTags($tags);
    }

    /**
     * @param array $tags
     * @return $this
     */
    public function removeByTags(array $tags): static
    {
        $tags = $this->_tag($tags);
        $this->_cache->removeByTags($tags);
        return $this;
    }

    /**
     * @param string $id
     * @return bool
     */
    protected function _isLocked(string $id): bool
    {
        if (!isset($this->_isLocked[$id])) {
            $isLocked = (int) $this->_cache->load($this->_lockKey . $id);
            $this->_isLocked[$id] = $isLocked ? Request::server()->REQUEST_TIME - $isLocked <= 100 : false;
        }
        return $this->_isLocked[$id];
    }

    /**
     * @param string $id
     * @param string $prefix
     * @return string
     */
    protected function _id(string $id, string $prefix = ''): string
    {
        return strtoupper(preg_replace('/[^\w\-\.]/', '_', $prefix . $id));
    }

    /**
     * Format tag name
     * @param array $tags
     * @return array
     */
    protected function _tag(array $tags): array
    {
        foreach ($tags as $k => $tag) {
            $tags[$k] = $this->_id($tag);
        }
        return $tags;
    }

    /**
     * Format cache data
     * @param mixed $data
     * @param int $lifetime
     * @return string
     */
    private function _prepareData(mixed $data, int $lifetime): string
    {
        return serialize(['data' => $data, 'lifetime' => $lifetime, 'expire' => Request::server()->REQUEST_TIME + $lifetime]);
    }

    /**
     * @param string $id
     * @return bool
     */
    private function _lock(string $id): bool
    {
        $this->_isLocked[$id] = (bool) $this->_cache->save($this->_lockKey . $id, Request::server()->REQUEST_TIME, [], 100);
        return $this->_isLocked[$id];
    }

    /**
     * @param string $id
     * @return bool
     */
    private function _unlock(string $id): bool
    {
        if (isset($this->_isLocked[$id]) && $this->_isLocked[$id]) {
            $this->_cache->remove($this->_lockKey . $id);
        }
        $this->_isLocked[$id] = false;
        return true;
    }

    private function _unlockAll(): bool
    {
        foreach ($this->_isLocked as $id => $isLocked) {
            if ($isLocked) {
                $this->_cache->remove($this->_lockKey . $id);
            }
        }
        return true;
    }
}

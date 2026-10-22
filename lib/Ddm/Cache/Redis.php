<?php

namespace Ddm\Cache;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Redis implements InterfaceCache
{

    protected array $_options = ['host' => '127.0.0.1', 'port' => 6379, 'timeout' => 1, 'database' => 0, 'password' => '', 'key_prefix' => 'ddmcms:cache:'];

    /** @var \Ddm\Redis */
    private ?\Ddm\Redis $_connection = null;

    public function __construct(array $options)
    {
        $this->_options = array_merge($this->_options, $options);
        if ($this->_options['key_prefix'] === null) {
            $this->_options['key_prefix'] = '';
        }

        $this->_connection = new \Ddm\Redis(
            $this->_options['host'],
            $this->_options['port'],
            $this->_options['password']
        );
        $this->_connection->setKeyPrefix($this->_options['key_prefix']);
        $this->_connection->setTimeout($this->_options['timeout']);
        $this->_connection->setDbId($this->_options['database']);
    }

    public function __destruct()
    {
        if ($this->_connection) {
            $this->_connection->close();
            $this->_connection = null;
        }
    }

    /**
     * @param string $id
     * @param string $data
     * @param array $tags
     * @param int $lifetime
     * @return bool
     */
    public function save(string $id, string $data, ?array $tags = null, int $lifetime = 0): bool
    {
        if ($id === 'TAGS_IDS') {
            throw new \InvalidArgumentException('The cache ID "TAGS_IDS" is reserved for internal use.');
        }
        $result = $this->_connection->set($id, $data, $lifetime);
        if (!$result) {
            return false;
        }

        if ($tags) {
            foreach ($tags as $tag) {
                $this->saveTag($id, $tag);
            }
        }

        $this->_saveTagNames([$id], $tags, false);
        return true;
    }

    public function saveTag(string $id, string $tag, bool $isRemove = false): bool
    {
        if ($tag === '' || $id === '') return false;

        $timeout = 10;
        $maxAttempts = $timeout * 5;
        $expireTime = max($timeout + 10, 60);
        $value = $_SERVER['REQUEST_TIME'];
        $key = 'save_tag_lock_'.$tag;
        $i = 0;

        while ($this->_connection->add($key, $value, $expireTime) == false) {
            if (++$i >= $maxAttempts) {
                return false;
            }
            usleep(200000);//200毫秒
        }

        $result = true;
        $tagKey = 'TAGS:'.$tag;
        try {
            $ids = $this->_connection->get($tagKey);
            if ($ids and $ids = json_decode($ids, true)) {
                if ($isRemove) {
                    $k = array_search($id, $ids);
                    if ($k !== false) {
                        unset($ids[$k]);
                        $ids = array_values($ids);
                    }
                } else if (!in_array($id, $ids)) {
                    $ids[] = $id;
                }
            } else if (!$isRemove) {
                $ids = [$id];
            }

            if ($ids) {
                $result = $this->_connection->set($tagKey, json_encode($ids, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
            }else if ($ids !== false) {
                $result = $this->_connection->delete($tagKey);
            }
        } finally {
            $this->_connection->delete($key); // 删除锁
        }
        return $result;
    }

    /**
     * @param string $id
     * @return string|false
     */
    public function load(string $id): string|false
    {
        return $this->_connection->get($id);
    }

    /**
     * @param string $id
     * @return bool
     */
    public function remove(string $id): bool
    {
        if ($id === 'TAGS_IDS') {
            throw new \InvalidArgumentException('The cache ID "TAGS_IDS" is reserved for internal use.');
        }
        return $this->_connection->delete($id, true);
    }

    /**
     * @param array $tags
     * @return bool
     */
    public function removeByTags(array $tags): bool
    {
        $ids = $this->getIdByTags($tags);
        foreach ($ids as $id) {
            $this->_connection->delete($id);
        }
        foreach ($tags as $tag) {
            $this->_connection->delete('TAGS:'.$tag);
        }
        $this->_saveTagNames($ids, $tags, true);
        return true;
    }

    /**
     * @return bool
     */
    public function clear(): bool
    {
        $tagKey = 'TAGS_IDS';
        $result = $this->_connection->get($tagKey);
        if (!$result or !($result = json_decode($result, true))) {
            return true;
        }
        if (!empty($result['ids'])) {
            foreach ($result['ids'] as $id) {
                $this->_connection->delete($id, true);
            }
        }
        if (!empty($result['tags'])) {
            foreach ($result['tags'] as $tag) {
                $this->_connection->delete('TAGS:'.$tag, true);
            }
        }
        $this->_connection->delete($tagKey);
        return true;
    }

    /**
     * @param array $tags
     * @return array
     */
    public function getIdByTags(array $tags): array
    {
        $ids = [];
        foreach ($tags as $tag) {
            $tagIds = $this->_connection->get('TAGS:'.$tag);
            if (!$tagIds or !($tagIds = json_decode($tagIds, true))) {
                continue;
            }
            foreach ($tagIds as $id) {
                $ids[$id] = $id;
            }
        }
        return $ids;
    }

    /**
     * @return array
     */
    public function getAllTags(): array
    {
        $tagKey = 'TAGS_IDS';
        $result = $this->_connection->get($tagKey);
        if (!$result or !($result = json_decode($result, true))) {
            return [];
        }
        return $result['tags'] ?? [];
    }

    protected function _saveTagNames(?array $ids, ?array $tags, bool $isRemove = false): bool
    {
        $timeout = 10;
        $maxAttempts = $timeout * 5;
        $expireTime = max($timeout + 10, 60);
        $value = $_SERVER['REQUEST_TIME'];
        $key = 'save_tags_ids_lock';
        $i = 0;

        while ($this->_connection->add($key, $value, $expireTime) == false) {
            if (++$i >= $maxAttempts) {
                return false;
            }
            usleep(200000);//200毫秒
        }

        $saveResult = true;
        $tagKey = 'TAGS_IDS';
        try {
            $result = $this->_connection->get($tagKey);
            if ($result and $result = json_decode($result, true)) {
                if ($isRemove) {
                    if ($ids === null) {
                        unset($result['ids']);
                    } else if (!empty($result['ids']) && $ids) {
                        $result['ids'] = array_values(array_diff($result['ids'], $ids));
                    }
                    if ($tags === null) {
                        unset($result['tags']);
                    } else if (!empty($result['tags']) && $tags) {
                        $result['tags'] = array_values(array_diff($result['tags'], $tags));
                    }
                } else {
                    if ($ids) {
                        $result['ids'] = array_values(array_unique(empty($result['ids']) ? $ids : array_merge($result['ids'], $ids)));
                    }
                    if ($tags) {
                        $result['tags'] = array_values(array_unique(empty($result['tags']) ? $tags : array_merge($result['tags'], $tags)));
                    }
                }
            } else if (!$isRemove && ($ids || $tags)) {
                $result = ['ids' => $ids, 'tags' => $tags];
            }

            if ($result) {
                $saveResult = $this->_connection->set($tagKey, json_encode($result, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
            }else if ($result !== false) {
                $saveResult = $this->_connection->delete($tagKey);
            }
        } finally {
            $this->_connection->delete($key); // 删除锁
        }
        return $saveResult;
    }
}

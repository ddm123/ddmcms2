<?php

namespace Ddm\Cache;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Apc implements InterfaceCache
{

    protected array $_options = ['prefix' => ''];

    protected string $_tagIdsKey = 'CACHE_TAG_IDS_DATA';

    protected string $_saveTagsLockKey = 'SAVE_TAGS_LOCK';

    private array $_ids = [];

    private ?array $_tagIds = null;

    private array $_tagsChangeLog = [];

    public function __construct(array $options)
    {
        $this->_options = array_merge($this->_options, $options);
        $this->_options['prefix'] == '' or $this->_options['prefix'] = strtoupper(preg_replace('/[^\w\-\.]/', '_', $this->_options['prefix']));
    }

    public function __destruct()
    {
        $this->_saveTagIds();
        unset($this->_ids, $this->_tagIds, $this->_tagsChangeLog);
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
        $result = apc_store($id, $data, $lifetime ? $lifetime + 180 : $lifetime);
        if ($tags) {
            $this->_tagIds ??= [];
            $this->_getTagIds();
            foreach ($tags as $tag) {
                $this->_tagIds[$tag][$id] = $id;
                $this->_tagsChangeLog[$tag][$id] = 'add';
            }
        }
        return $result;
    }

    /**
     * @param string $id
     * @return string|false
     */
    public function load(string $id): string|false
    {
        $result = apc_fetch($id);
        return $result ? $result : false;
    }

    /**
     * @param string $id
     * @return bool
     */
    public function remove(string $id): bool
    {
        foreach ($this->_getTagIds() as $tag => $ids) {
            if (isset($ids[$id])) {
                if ($this->_tagIds) unset($this->_tagIds[$tag][$id]);
                $this->_tagsChangeLog[$tag][$id] = 'delete';
            }
        }
        return apc_delete($id);
    }

    /**
     * @param array $tags
     * @return bool
     */
    public function removeByTags(array $tags): bool
    {
        $tagIds = $this->_getTagIds();
        foreach ($tags as $tag) {
            if (isset($tagIds[$tag])) {
                foreach ($tagIds[$tag] as $id) {
                    apc_delete($id);
                }
                if (isset($this->_tagIds, $this->_tagIds[$tag])) unset($this->_tagIds[$tag]);
                $this->_tagsChangeLog[$tag] = [];
            }
        }
        return true;
    }

    /**
     * @return bool
     */
    public function clear(): bool
    {
        $this->_tagIds = [];
        $this->_tagsChangeLog = ['_clear_all' => true];
        return apc_clear_cache('user');
    }

    /**
     * @param array $tags
     * @return array
     */
    public function getIdByTags(array $tags): array
    {
        $ids = [];
        $tagIds = $this->_getTagIds();
        foreach ($tags as $tag) {
            if (isset($tagIds[$tag])) {
                $ids = array_merge($ids, $tagIds[$tag]);
            }
        }
        return $ids;
    }

    /**
     * @return array
     */
    public function getAllTags(): array
    {
        $tags = [];
        if ($tagIds = $this->_getTagIds()) {
            $tags = array_keys($tagIds);
        }
        return $tags;
    }

    /**
     * @param bool $reload
     * @return array
     */
    protected function _getTagIds(bool $reload = false): array
    {
        if ($this->_tagIds === null || $reload) {
            if ($tagIds = apc_fetch($this->_options['prefix'] . $this->_tagIdsKey)) {
                $this->_tagIds = unserialize($tagIds);
            } else {
                $this->_tagIds = [];
            }
        }
        return $this->_tagIds;
    }

    /**
     * @return $this
     */
    protected function _saveTagIds(): static
    {
        if ($this->_tagsChangeLog) {
            $i = 0;
            while (!apc_add($this->_options['prefix'] . $this->_saveTagsLockKey, $i, 30)) {
                if ($i++ > 300) {
                    break;
                }
                usleep(100000);
                //等待100毫秒再继续尝试
            }
            if (isset($this->_tagsChangeLog['_clear_all']) && count($this->_tagsChangeLog) == 1) {
                $this->_tagIds = [];
            } else {
                $this->_getTagIds(true);
                if (isset($this->_tagsChangeLog['_clear_all'])) {
                    unset($this->_tagsChangeLog['_clear_all']);
                }
                foreach ($this->_tagsChangeLog as $tag => $ids) {
                    if ($ids) {
                        foreach ($ids as $id => $value) {
                            switch ($value) {
                                case 'add':
                                    $this->_tagIds[$tag][$id] = $id;
                                    break;
                                case 'delete':
                                    unset($this->_tagIds[$tag][$id]);
                                    break;
                            }
                        }
                    } else {
                        unset($this->_tagIds[$tag]);
                    }
                }
            }
            apc_store($this->_options['prefix'] . $this->_tagIdsKey, serialize($this->_tagIds), 0);
            apc_delete($this->_options['prefix'] . $this->_saveTagsLockKey);
            $this->_tagsChangeLog = [];
        }
        return $this;
    }
}

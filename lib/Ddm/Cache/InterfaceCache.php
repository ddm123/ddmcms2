<?php

namespace Ddm\Cache;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
interface InterfaceCache
{

    public function __construct(array $options);

    /**
     * @param string $id
     * @param string $data
     * @param array $tags
     * @param int $lifetime
     * @return bool
     */
    public function save(string $id, string $data, ?array $tags = null, int $lifetime = 0): bool;

    /**
     * @param string $id
     * @return string|false
     */
    public function load(string $id): string|false;

    /**
     * @param string $id
     * @return bool
     */
    public function remove(string $id): bool;

    /**
     * @param array $tags
     * @return bool
     */
    public function removeByTags(array $tags): bool;

    /**
     * @return bool
     */
    public function clear(): bool;

    /**
     * @param array $tags
     * @return array
     */
    public function getIdByTags(array $tags): array;

    /**
     * @return array
     */
    public function getAllTags(): array;
}

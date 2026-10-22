<?php

namespace Ddm\Cache;

use InvalidArgumentException;
use RuntimeException;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class File implements InterfaceCache
{

    protected array $_options = ['cache_dir' => 'data/cache/data-cache', 'dir_mode' => 0755];

    public function __construct(array $options)
    {
        $this->_options = array_merge($this->_options, $options);
        if (!is_string($this->_options['cache_dir'])) {
            throw new InvalidArgumentException('The cache directory must be a relative path.');
        }
        if (str_contains($this->_options['cache_dir'], '..')) {
            throw new InvalidArgumentException('The cache directory must be inside the data directory.');
        }
        $this->_options['cache_dir'] = ltrim($this->_options['cache_dir'], '/\\');
        if ($this->_options['cache_dir'] === '' || $this->_options['cache_dir'] === '.') {
            throw new InvalidArgumentException('The cache directory must not be the site root.');
        }

        if (!$this->_options['dir_mode']) {
            $this->_options['dir_mode'] = 0755;
        } elseif (is_string($this->_options['dir_mode'])) {
            $this->_options['dir_mode'] = octdec($this->_options['dir_mode']);
        }
    }

    /**
     * 返回绝对路径，末尾不带斜杠
     */
    public function getCacheDir(): string
    {
        return SITE_ROOT . DIRECTORY_SEPARATOR . $this->_options['cache_dir'];
    }

    public function save(string $id, string $data, ?array $tags = null, int $lifetime = 0): bool
    {
        [$dir, $name] = $this->_getFileName($id);
        if (!$this->_ensureDirectory($dir)) {
            throw new RuntimeException('Unable to create cache directory.');
        }

        $expiresAt = $lifetime > 0 ? time() + $lifetime : 0;
        $content = 'Expires At:' . $expiresAt . "\n" . $data;
        $tmpName = $dir . DIRECTORY_SEPARATOR . $name . mt_rand(10000, 99999) . '.tmp';
        if (!file_put_contents($tmpName, $content)) {
            return false;
        }
        if (!rename($tmpName, $dir . DIRECTORY_SEPARATOR . $name)) {
            @unlink($tmpName);
            return false;
        }

        if ($tags) {
            foreach ($tags as $tag) {
                $this->saveTag($id, $tag);
            }
        }

        return true;
    }

    public function saveTag(string $id, string $tag, bool $isRemove = false): bool
    {
        if ($id === '' || $tag === '') {
            return false;
        }

        $lock = $this->_acquireTagLock($tag);
        if ($lock === false) {
            return false;
        }

        $result = true;
        try {
            [$dir, $name] = $this->_getTagFile($tag);

            $path = $dir . DIRECTORY_SEPARATOR . $name;
            $tagIds = ['tag' => $tag, 'ids' => null];
            if (is_file($path)) {
                $content = file_get_contents($path);
                if ($content === false) {
                    throw new RuntimeException('Unable to read cache tag index: ' . $tag);
                }
                $tagIds = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
                if (!$tagIds) {
                    throw new RuntimeException('Invalid cache tag index: ' . $tag);
                }
                $tagIds['tag'] = $tag;
            }

            if ($tagIds['ids']) {
                if ($isRemove) {
                    $k = array_search($id, $tagIds['ids']);
                    if ($k !== false) {
                        unset($tagIds['ids'][$k]);
                        $tagIds['ids'] = array_values($tagIds['ids']);
                    }
                } else if (!in_array($id, $tagIds['ids'])) {
                    $tagIds['ids'][] = $id;
                }
            } else if (!$isRemove) {
                $tagIds['ids'] = [$id];
            }

            if ($tagIds['ids']) {
                if (!$this->_ensureDirectory($dir)) {
                    throw new RuntimeException('Unable to create cache directory.');
                }

                $tmpName = $path . mt_rand(10000, 99999) . '.tmp';
                $result = file_put_contents($tmpName, json_encode($tagIds, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
                if ($result !== false) {
                    if (!rename($tmpName, $path)) {
                        @unlink($tmpName);
                        $result = false;
                    }
                }
            }else if ($tagIds['ids'] !== null) {
                $result = unlink($path);
            }
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }

        return $result;
    }

    public function load(string $id): string|false
    {
        [$dir, $name] = $this->_getFileName($id);
        $path = $dir . DIRECTORY_SEPARATOR . $name;
        if (!is_file($path)) {
            return false;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return false;
        }

        $headerEnd = strpos($content, "\n");
        if ($headerEnd === false) {
            return $content;
        }

        $expiresAt = substr($content, 0, $headerEnd);
        if (!str_starts_with($expiresAt, 'Expires At:')) {
            return $content;
        }

        $expiresAt = (int)substr($expiresAt, 11);
        if ($expiresAt > 0 && $expiresAt < $_SERVER['REQUEST_TIME']) {
            unlink($path);
            return false;
        }

        return substr($content, $headerEnd + 1);
    }

    public function remove(string $id): bool
    {
        [$dir, $name] = $this->_getFileName($id);
        $path = $dir . DIRECTORY_SEPARATOR . $name;

        return !file_exists($path) || unlink($path);
    }

    public function removeByTags(array $tags): bool
    {
        $result = true;
        if ($ids = $this->getIdByTags($tags)) {
            $result = !in_array(false, array_map($this->remove(...), $ids), true);
        }
        foreach ($tags as $tag) {
            [$dir, $name] = $this->_getTagFile($tag);
            $path = $dir . DIRECTORY_SEPARATOR . $name;
            if (file_exists($path)) {
                if (!@unlink($path)) {
                    $result = false;
                }
            }
        }

        return $result;
    }

    public function clear(): bool
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->getCacheDir(), \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        $result = true;

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            $path = $file->getPathname();

            // getType() 基于 lstat：符号链接一律返回 'link'，不管它指向哪
            if ($file->getType() === 'dir') {
                if (!@rmdir($path)) {
                    //throw new \RuntimeException("无法删除目录: {$path}");
                    $result = false;
                }
            } else {
                // 'file' / 'link' / 'fifo' / 'socket' 全部走 unlink
                if (!@unlink($path)) {
                    //throw new \RuntimeException("无法删除文件: {$path}");
                    $result = false;
                }
            }
        }
        return $result;
    }

    public function getIdByTags(array $tags): array
    {
        $ids = [];
        foreach ($tags as $tag) {
            [$dir, $name] = $this->_getTagFile($tag);

            $path = $dir . DIRECTORY_SEPARATOR . $name;
            if (!is_file($path)) {
                continue;
            }

            $content = file_get_contents($path);
            if ($content === false) {
                continue;
            }

            $tagIds = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            if (!$tagIds || empty($tagIds['ids'])) {
                continue;
            }

            foreach ($tagIds['ids'] as $id) {
                $ids[$id] = $id;
            }
        }
        return $ids;
    }

    public function getAllTags(): array
    {
        [$dir,] = $this->_getTagFile('tags');
        if (!is_dir($dir)) {
            return [];
        }

        $tags = [];
        foreach (new \DirectoryIterator($dir) as $entry) {
            if (!$entry->isDot() && $entry->isFile()) {
                $content = file_get_contents($entry->getPathname());
                if ($content === false) {
                    continue;
                }

                $tagIds = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
                if (!$tagIds || empty($tagIds['tag'])) {
                    continue;
                }

                $tags[] = $tagIds['tag'];
            }
        }

        return $tags;
    }

    protected function _getFileName(string $id): array
    {
        $key = md5($id);
        return [$this->getCacheDir() . DIRECTORY_SEPARATOR . $key[0] . $key[1] . DIRECTORY_SEPARATOR . $key[2], $key];
    }

    private function _getTagFile(string $tag): array
    {
        return [$this->getCacheDir() . DIRECTORY_SEPARATOR . '_tags', md5($tag)];
    }

    private function _ensureDirectory(string $directory): bool
    {
        return is_dir($directory) || mkdir($directory, $this->_options['dir_mode'], true);
    }

    /**
     * @return resource|false
     */
    private function _acquireTagLock(string $tag)
    {
        $lockDirectory = $this->getCacheDir() . DIRECTORY_SEPARATOR . '_locks';
        if (!$this->_ensureDirectory($lockDirectory)) {
            return false;
        }

        $lockFile = $lockDirectory . DIRECTORY_SEPARATOR . md5($tag) . '.lock';
        $handle = @fopen($lockFile, 'c');
        if ($handle === false) {
            return false;
        }

        for ($attempt = 0; $attempt < 50; $attempt++) {
            if (flock($handle, LOCK_EX | LOCK_NB)) {
                return $handle;
            }
            usleep(200000);//200毫秒
        }

        fclose($handle);
        return false;
    }
}

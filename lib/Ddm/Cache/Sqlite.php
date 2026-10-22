<?php

namespace Ddm\Cache;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */

use PDO;
use PDOStatement;

class Sqlite implements InterfaceCache
{

    protected array $_options = ['db_file' => 'data/cache/data-cache/cache.sq3', 'dir_mode' => 0755];

    /** @var \PDO */
    private ?PDO $_SQLite = null;

    /** @var \PDOStatement */
    private ?PDOStatement $_saveStatement = null;

    /** @var \PDOStatement */
    private ?PDOStatement $_loadStatement = null;

    public function __construct(array $options)
    {
        $this->_options = array_merge($this->_options, $options);
        if (!$this->_options['dir_mode']) {
            $this->_options['dir_mode'] = 0755;
        } elseif (is_string($this->_options['dir_mode'])) {
            $this->_options['dir_mode'] = octdec($this->_options['dir_mode']);
        }

        $path = dirname(SITE_ROOT . DIRECTORY_SEPARATOR . $this->_options['db_file']);
        if (!is_dir($path) && !@mkdir($path, $this->_options['dir_mode'], true) && !is_dir($path)) {
            throw new \Exception('Unable to create cache directory: ' . $path);
        }
        $this->getConnection();
    }

    /**
     * 返回绝对路径
     * @return string
     */
    public function getCacheDir(): string
    {
        return SITE_ROOT . DIRECTORY_SEPARATOR . $this->_options['db_file'];
    }

    /**
     * @return PDO
     */
    public function getConnection(): PDO
    {
        if ($this->_SQLite === null) {
            $isExistsDb = is_file(SITE_ROOT . DIRECTORY_SEPARATOR . $this->_options['db_file']);
            $this->_SQLite = new PDO('sqlite:' . SITE_ROOT . DIRECTORY_SEPARATOR . $this->_options['db_file'], null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]);
            $isExistsDb or $this->_buildStructure($this->_SQLite);
            $this->_saveStatement = $this->_SQLite->prepare('REPLACE INTO cache(id,content,expire) VALUES(:id,:content,:expire)');
            $this->_loadStatement = $this->_SQLite->prepare('SELECT content,expire FROM cache WHERE id = :id');
        }
        return $this->_SQLite;
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
        $this->_saveStatement->execute([':id' => $id, ':content' => $data, ':expire' => $lifetime ? $lifetime + $_SERVER['REQUEST_TIME'] : 0]);
        if ($tags) {
            $this->_saveTagIds($id, $tags);
        }
        return $this->_saveStatement->rowCount() > 0;
    }

    /**
     * @param string $id
     * @return string|false
     */
    public function load(string $id): string|false
    {
        $this->_loadStatement->execute([':id' => $id]);
        $result = $this->_loadStatement->fetch(PDO::FETCH_ASSOC);
        $this->_loadStatement->closeCursor();
        if ($result) {
            if ($result['expire'] && $result['expire'] < $_SERVER['REQUEST_TIME']) {
                $this->remove($id);
                return false;
            }
            return $result['content'];
        }
        return false;
    }

    /**
     * @param string $id
     * @return bool
     */
    public function remove(string $id): bool
    {
        $rid = $rtag = false;
        $rid = $this->_exec("DELETE FROM cache WHERE id=?", [$id]);
        $rtag = $this->_exec("DELETE FROM tag WHERE id=?", [$id]);
        return $rid && $rtag;
    }

    /**
     * @param array $tags
     * @return bool
     */
    public function removeByTags(array $tags): bool
    {
        $rid = $rtag = false;
        if ($ids = $this->getIdByTags($tags)) {
            $rid = $this->_exec("DELETE FROM cache WHERE id IN(" . $this->_getPlaceHolders($ids) . ")", array_values($ids));
            $rtag = $this->_exec("DELETE FROM tag WHERE name IN(" . $this->_getPlaceHolders($tags) . ")", array_values($tags));
        }
        return $rid && $rtag;
    }

    /**
     * @return bool
     */
    public function clear(): bool
    {
        //$this->_exec('DROP INDEX tag_name_id');
        //$this->_exec('DROP INDEX tag_id_index');
        $this->_exec("DROP TABLE cache");
        $this->_exec("DROP TABLE tag");
        $this->_buildStructure($this->_SQLite);
        return true;
    }

    /**
     * @param array $tags
     * @return array
     */
    public function getIdByTags(array $tags): array
    {
        $ids = [];
        if ($tags) {
            $stmt = $this->_SQLite->prepare("SELECT DISTINCT(id) AS id FROM tag WHERE name IN(" . $this->_getPlaceHolders($tags) . ")");
            $stmt->execute(array_values($tags));
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                $ids[$row[0]] = $row[0];
            }
            $stmt->closeCursor();
        }
        return $ids;
    }

    /**
     * @return array
     */
    public function getAllTags(): array
    {
        $sth = $this->_SQLite->prepare("SELECT DISTINCT(name) AS name FROM tag");
        $sth->execute();
        $tags = $sth->fetchAll(PDO::FETCH_COLUMN, 0);
        $sth->closeCursor();
        return $tags;
    }

    /**
     * @param array $value
     * @return string
     */
    protected function _getPlaceHolders(array $value): string
    {
        $len = count($value);
        if ($len === 1) {
            return '?';
        }
        if ($len === 2) {
            return '?,?';
        }
        return '?' . str_repeat(',?', $len - 1);
    }

    /**
     * @param string $id
     * @param array $tags
     * @return $this
     */
    protected function _saveTagIds(string $id, array $tags): static
    {
        foreach ($tags as $tag) {
            $this->_exec("INSERT OR IGNORE INTO tag(name,id) VALUES (?,?)", [$tag, $id]);
        }
        return $this;
    }

    /**
     * @param string $sql
     * @param array $params
     * @return int
     */
    protected function _exec(string $sql, array $params = []): int
    {
        if ($params) {
            $sth = $this->_SQLite->prepare($sql);
            $sth->execute($params);
            return $sth->rowCount();
        }
        return $this->_SQLite->exec($sql);
    }

    /**
     * @param PDO $sqlite
     * @return $this
     */
    private function _buildStructure(PDO $sqlite): static
    {
        $sqlite->exec('CREATE TABLE cache (id VARCHAR(255) PRIMARY KEY, content BLOB,expire INT(10))');
        $sqlite->exec('CREATE TABLE tag (name VARCHAR(255), id VARCHAR(255))');
        $sqlite->exec('CREATE UNIQUE INDEX tag_name_id ON tag(name,id)');
        $sqlite->exec('CREATE INDEX tag_id_index ON tag(id)');
        return $this;
    }
}

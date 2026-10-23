<?php

namespace Ddm\Session;

use Ddm;
use Ddm\Cookie;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class File implements InterfaceSession
{

    protected ?float $_lifeTime = null;

    protected int $_gcProbability = 30;

    protected string $_savePath = '';

    protected array $_sessions = [];

    protected array $_sessionsInfo = [];

    protected string $_sessionsInfoFileName = 'sessionsinfo.txt';

    protected string $_sessionsInfoLockFileName = 'sessionsinfo.lock';

    /**
     * @param string $id
     * @return string
     */
    protected function _getPath(string $id): string
    {
        $path = "/{$id[0]}/{$id[1]}";
        is_dir($this->_savePath . $path) or mkdir($this->_savePath . $path, 0755, true);
        return $path;
    }

    /**
     * @param string $id
     * @return string
     */
    protected function _getFile(string $id): string
    {
        return $this->_getPath($id) . "/sess_{$id}";
    }

    /**
     * @return array
     */
    protected function _getSessionsInfoFromFile(): array
    {
        $sessionsInfo = [];
        if (is_file($this->_savePath . '/' . $this->_sessionsInfoFileName)) {
            $sessionsInfo = unserialize(file_get_contents($this->_savePath . '/' . $this->_sessionsInfoFileName));
        }
        return $sessionsInfo;
    }

    /**
     * @return $this
     */
    protected function _saveSessionsInfoToFile(): static
    {
        if ($this->_sessionsInfo) {
            $i = 0;
            if (file_exists($this->_savePath . '/' . $this->_sessionsInfoLockFileName) && filemtime($this->_savePath . '/' . $this->_sessionsInfoLockFileName) + 30 < $_SERVER['REQUEST_TIME']) {
                unlink($this->_savePath . '/' . $this->_sessionsInfoLockFileName);
            }
            while (!$fp = @fopen($this->_savePath . '/' . $this->_sessionsInfoLockFileName, 'x', false)) {
                if (++$i >= 20) {
                    return $this;
                }
                usleep(500000);
                //500毫秒
            }
            fclose($fp);
            $origSessionsInfo = $this->_getSessionsInfoFromFile();
            foreach ($this->_sessionsInfo as $id => $data) {
                if ($data) {
                    $origSessionsInfo[$id] = $data;
                } else {
                    unset($origSessionsInfo[$id]);
                }
            }
            file_put_contents($this->_savePath . '/' . $this->_sessionsInfoFileName, serialize($origSessionsInfo));
            unlink($this->_savePath . '/' . $this->_sessionsInfoLockFileName);
        }
        return $this;
    }

    public function open(mixed $savePath, mixed $sessionName): bool
    {
        return (bool) $this->_savePath;
    }

    public function close(): bool
    {
        $this->gc($this->getLifeTime());
        $this->_saveSessionsInfoToFile();
        //unset($this->_sessionsInfo,$this->_sessions);
        return true;
    }

    public function read(mixed $id): string|false
    {
        if (!isset($this->_sessions[$id])) {
            $file = $this->_savePath . $this->_getFile($id);
            $this->_sessions[$id] = is_file($file) ? file_get_contents($file) : '';
        }
        return $this->_sessions[$id];
    }

    public function write(mixed $id, mixed $data): bool
    {
        $file = $this->_getFile($id);
        $this->_sessionsInfo[$id] = [$_SERVER['REQUEST_TIME'], $file];
        $this->_sessions[$id] = $data;
        return file_put_contents($this->_savePath . $file, $data) === false ? false : true;
    }

    public function destroy(mixed $id): bool
    {
        $file = $this->_savePath . $this->_getFile($id);
        $this->_sessions[$id] = '';
        $this->_sessionsInfo[$id] = false;
        return is_file($file) && unlink($file);
    }

    public function gc(int $maxlifetime): bool
    {
        if ($this->_gcProbability < 1 || $this->_gcProbability != 1 && mt_rand(1, $this->_gcProbability) != 1) {
            return true;
        }
        if ($sessionsInfo = $this->_getSessionsInfoFromFile()) {
            foreach ($sessionsInfo as $id => $data) {
                if ($data[0] + $maxlifetime < $_SERVER['REQUEST_TIME']) {
                    if (is_file($file = $this->_savePath . $data[1])) {
                        unlink($file);
                    }
                    $this->_sessions[$id] = '';
                    $this->_sessionsInfo[$id] = false;
                }
            }
        }
        return true;
    }

    /**
     * @return int
     */
    public function getLifeTime(): int
    {
        if ($this->_lifeTime === null) {
            $this->_lifeTime = (float) Ddm::getConfig()->getConfigValue(Cookie::CONFIG_PATH_COOKIE_LIFETIME) * 60;
            if ($this->_lifeTime < 60) {
                $this->_lifeTime = (int) ini_get('session.gc_maxlifetime') or $this->_lifeTime = 1440;
            }
        }
        return $this->_lifeTime;
    }

    public function setSaveHandler(): static
    {
        session_set_save_handler([$this, 'open'], [$this, 'close'], [$this, 'read'], [$this, 'write'], [$this, 'destroy'], [$this, 'gc']);
        $path = SITE_ROOT . '/data/session';
        $this->_savePath = is_dir($path) || mkdir($path, 0755, true) ? $path : '';
        return $this;
    }
}

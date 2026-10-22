<?php

namespace Ddm\Session;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
interface InterfaceSession
{

    /**
     * @return $this
     */
    public function setSaveHandler(): static;

    /**
     * @return bool
     */
    public function close(): bool;

    /**
     * @param string $sessionId
     * @return bool
     */
    public function destroy(string $sessionId): bool;

    /**
     * @param string $maxlifetime
     * @return bool
     */
    public function gc(int $maxlifetime): bool;

    /**
     * @param string $savePath
     * @param string $name
     * @return bool
     */
    public function open(string $savePath, string $name): bool;

    /**
     * @param string $sessionId
     * @return string
     */
    public function read(string $sessionId): string|false;

    /**
     * @param string $sessionId
     * @param string $sessionData
     * @return bool
     */
    public function write(string $sessionId, string $sessionData): bool;
}

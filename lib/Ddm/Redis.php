<?php

namespace Ddm;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Redis
{
    /**
     * @var array<string, array{type: string, resource: mixed, currentDb: int}>
     */
    private static array $_connections = [];

    private string $_connectionKey = '';

    private string $_host = '127.0.0.1';

    private int $_port = 6379;

    private string $_password = '';

    protected int $_dbId = 0;

    protected string $_keyPrefix = 'ddmcms:';

    protected int $_timeout = 2;

    public function __construct(string $host = '127.0.0.1', int $port = 6379, string $password = '')
    {
        $this->_host = $host;
        $this->_port = $port;
        $this->_password = $password;
        $this->_connectionKey = "{$host}|{$port}|{$password}";
        $this->ensureConnected();
    }

    /**
     * 返回底层原生连接：
     *   扩展模式 → \Redis 对象
     *   Socket 模式 → socket resource
     *
     * 注意：如果之前 close() 过，这里会触发惰性重连。
     *
     * @return \Redis|resource
     */
    public function getConnection()
    {
        $this->ensureConnected();
        return self::$_connections[$this->_connectionKey]['resource'];
    }

    /**
     * 当前实例走的是不是 PhpRedis 扩展模式
     */
    public function isExtensionMode(): bool
    {
        $this->ensureConnected();
        return self::$_connections[$this->_connectionKey]['type'] === 'extension';
    }

    /**
     * 关闭当前实例对应的连接（从共享池中移除并释放资源）
     */
    public function close(): static
    {
        if (isset(self::$_connections[$this->_connectionKey])) {
            self::closeResource(self::$_connections[$this->_connectionKey]);
            unset(self::$_connections[$this->_connectionKey]);
        }
        return $this;
    }

    /**
     * 关闭所有连接（常驻进程在退出/重启前调用）
     */
    public static function closeAll(): void
    {
        foreach (self::$_connections as $conn) {
            self::closeResource($conn);
        }
        self::$_connections = [];
    }

    public function setDbId(int $dbId): static
    {
        $this->_dbId = $dbId;
        $this->ensureDb();
        return $this;
    }

    public function setKeyPrefix(string $keyPrefix): static
    {
        $this->_keyPrefix = $keyPrefix;
        return $this;
    }

    /**
     * 更新读超时时间，单位秒
     */
    public function setTimeout(int $timeout): static
    {
        $this->_timeout = $timeout;
        $this->ensureConnected();

        if (self::$_connections[$this->_connectionKey]['type'] === 'extension') {
            self::$_connections[$this->_connectionKey]['resource']->setOption(\Redis::OPT_READ_TIMEOUT, $timeout);
        } else {
            stream_set_timeout(self::$_connections[$this->_connectionKey]['resource'], $timeout);
        }
        return $this;
    }

    public function add(string $key, string $value, int $lifetime = 0): bool
    {
        $args = ['SET', $this->buildKey($key), $value, 'NX'];
        if ($lifetime > 0) {
            $args[] = 'EX';
            $args[] = $lifetime;
        }
        $result = $this->command($args);
        return $result !== false && $result !== null;
    }

    public function set(string $key, string $value, int $lifetime = 0): bool
    {
        $args = ['SET', $this->buildKey($key), $value];
        if ($lifetime > 0) {
            $args[] = 'EX';
            $args[] = $lifetime;
        }
        $result = $this->command($args);
        return $result !== false && $result !== null;
    }

    public function update(string $key, string $value, int $lifetime = 0): bool
    {
        $args = ['SET', $this->buildKey($key), $value, 'XX'];
        if ($lifetime > 0) {
            $args[] = 'EX';
            $args[] = $lifetime;
        }
        $result = $this->command($args);
        return $result !== false && $result !== null;
    }

    public function get(string $key): string|false
    {
        $result = $this->command(['GET', $this->buildKey($key)]);
        if ($result === false || $result === null) {
            return false;
        }
        return (string)$result;
    }

    public function delete(string $key, bool $ignoreNotExists = true): bool
    {
        $result = $this->command(['DEL', $this->buildKey($key)]);
        if ((int)$result > 0) {
            return true;
        }
        return $ignoreNotExists;
    }

    protected function ensureConnected(): void
    {
        if (!isset(self::$_connections[$this->_connectionKey])) {
            self::$_connections[$this->_connectionKey] = $this->_connect($this->_host, $this->_port, $this->_password);
        }
    }

    protected function buildKey(string $key): string
    {
        return $this->_keyPrefix . $key;
    }

    protected function command(array $args)
    {
        $this->ensureDb();
        return $this->rawCommand($args);
    }

    /**
     * 切换数据库（共享连接每次核对，相同则跳过 SELECT）
     */
    protected function ensureDb(): void
    {
        $this->ensureConnected();

        if (self::$_connections[$this->_connectionKey]['currentDb'] == $this->_dbId) {
            return;
        }

        if (self::$_connections[$this->_connectionKey]['type'] === 'extension') {
            if (!self::$_connections[$this->_connectionKey]['resource']->select($this->_dbId)) {
                throw new \RuntimeException("Redis 切换数据库失败: {$this->_dbId}");
            }
        } else {
            $this->socketWrite(self::$_connections[$this->_connectionKey]['resource'], ['SELECT', $this->_dbId]);
            $result = $this->socketRead(self::$_connections[$this->_connectionKey]['resource']);
            if ($result !== 'OK' && $result !== true) {
                throw new \RuntimeException("Redis 切换数据库失败: {$this->_dbId}");
            }
        }
        self::$_connections[$this->_connectionKey]['currentDb'] = $this->_dbId;
    }

    protected function rawCommand(array $args)
    {
        $this->ensureConnected();

        if (self::$_connections[$this->_connectionKey]['type'] === 'extension') {
            $cmd = array_shift($args);
            $result = self::$_connections[$this->_connectionKey]['resource']->rawCommand($cmd, ...$args);
            return $result === null ? false : $result;
        }

        $this->socketWrite(self::$_connections[$this->_connectionKey]['resource'], $args);
        return $this->socketRead(self::$_connections[$this->_connectionKey]['resource']);
    }

    /**
     * 建立底层连接
     *
     * @return array{type: string, resource: mixed, currentDb: int}
     */
    protected function _connect(string $host, int $port, string $password): array
    {
        if (extension_loaded('redis') && class_exists('\Redis', false)) {
            $redis = new \Redis();
            if (!$redis->connect($host, $port, $this->_timeout)) {
                throw new \RuntimeException("Redis 连接失败: {$host}:{$port}");
            }
            if ($password !== '' && !$redis->auth($password)) {
                throw new \RuntimeException('Redis 认证失败');
            }
            return ['type' => 'extension', 'resource' => $redis, 'currentDb' => $this->_dbId];
        }

        $address = "tcp://{$host}:{$port}";
        $errno = 0;
        $errstr = '';
        $socket = @stream_socket_client($address, $errno, $errstr, $this->_timeout);
        if ($socket === false) {
            throw new \RuntimeException("Redis 连接失败: {$address} ({$errno}: {$errstr})");
        }
        stream_set_timeout($socket, $this->_timeout);
        stream_set_blocking($socket, true);

        if ($password !== '') {
            $this->socketWrite($socket, ['AUTH', $password]);
            $result = $this->socketRead($socket);
            if ($result !== 'OK' && $result !== true) {
                throw new \RuntimeException('Redis 认证失败');
            }
        }

        return ['type' => 'socket', 'resource' => $socket, 'currentDb' => $this->_dbId];
    }

    /**
     * 释放一条连接描述结构对应的底层资源
     */
    private static function closeResource(array $conn): void
    {
        try {
            if ($conn['type'] === 'extension') {
                $conn['resource']->close();
            } else {
                if (is_resource($conn['resource'])) {
                    fclose($conn['resource']);
                }
            }
        } catch (\Throwable) {
        }
    }

    /**
     * @param resource $socket
     * @param array<int, mixed> $args
     */
    private function socketWrite($socket, array $args): void
    {
        $payload = '*' . count($args) . "\r\n";
        foreach ($args as $arg) {
            $arg = (string)$arg;
            $payload .= '$' . strlen($arg) . "\r\n" . $arg . "\r\n";
        }

        $total = strlen($payload);
        $offset = 0;
        while ($offset < $total) {
            $written = @fwrite($socket, substr($payload, $offset));
            if ($written === false || $written === 0) {
                throw new \RuntimeException('Redis 写入失败');
            }
            $offset += $written;
        }
    }

    /**
     * @param resource $socket
     * @return mixed false 表示 nil
     */
    private function socketRead($socket)
    {
        $line = $this->socketReadLine($socket);
        $type = $line[0];
        $data = substr($line, 1, -2);

        switch ($type) {
            case '+':
                return $data;
            case '-':
                throw new \RuntimeException('Redis 错误: ' . $data);
            case ':':
                return (int)$data;
            case '$':
                $len = (int)$data;
                if ($len < 0) {
                    return false;
                }
                $content = $this->socketReadBytes($socket, $len);
                $this->socketReadBytes($socket, 2);
                return $content;
            case '*':
                $count = (int)$data;
                if ($count < 0) {
                    return false;
                }
                $result = [];
                for ($i = 0; $i < $count; $i++) {
                    $result[] = $this->socketRead($socket);
                }
                return $result;
            default:
                throw new \RuntimeException('未知的 Redis 响应: ' . $line);
        }
    }

    /**
     * @param resource $socket
     */
    private function socketReadLine($socket): string
    {
        $line = fgets($socket);
        if ($line === false) {
            throw $this->socketReadError($socket);
        }
        return $line;
    }

    /**
     * @param resource $socket
     */
    private function socketReadBytes($socket, int $len): string
    {
        $buf = '';
        while (strlen($buf) < $len) {
            $chunk = fread($socket, $len - strlen($buf));
            if ($chunk === false || $chunk === '') {
                throw $this->socketReadError($socket);
            }
            $buf .= $chunk;
        }
        return $buf;
    }

    /**
     * @param resource $socket
     */
    private function socketReadError($socket): \RuntimeException
    {
        $meta = stream_get_meta_data($socket);
        return new \RuntimeException($meta['timed_out'] ? 'Redis 读取超时' : 'Redis 连接已断开');
    }
}

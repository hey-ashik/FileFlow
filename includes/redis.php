<?php
/**
 * FileFlow - Redis Cache Helper
 * 
 * High-performance key-value cache with automatic fallback capability.
 */

require_once __DIR__ . '/../config/database.php';

class RedisCache {
    private static $instance = null;
    private $redis = null;
    private $fallback = false;

    private function __construct() {
        if (!defined('REDIS_ENABLED') || !REDIS_ENABLED) {
            $this->fallback = true;
            return;
        }

        try {
            if (class_exists('Redis')) {
                $this->redis = new Redis();
                if ($this->redis->connect(REDIS_HOST, REDIS_PORT, 2.0)) {
                    if (defined('REDIS_PASS') && REDIS_PASS !== '') {
                        $this->redis->auth(REDIS_PASS);
                    }
                    if (defined('REDIS_DB')) {
                        $this->redis->select(REDIS_DB);
                    }
                } else {
                    $this->fallback = true;
                }
            } else {
                // Fallback to socket connection
                $this->redis = new SocketRedis(REDIS_HOST, REDIS_PORT, REDIS_PASS, REDIS_DB);
                if (!$this->redis->isConnected()) {
                    $this->fallback = true;
                }
            }
        } catch (Exception $e) {
            error_log("Redis connection error: " . $e->getMessage());
            $this->fallback = true;
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get($key) {
        if ($this->fallback || !$this->redis) {
            return null;
        }
        try {
            $value = $this->redis->get($key);
            if ($value === false || $value === null) {
                return null;
            }
            return unserialize($value);
        } catch (Exception $e) {
            return null;
        }
    }

    public function set($key, $value, $ttl = 3600) {
        if ($this->fallback || !$this->redis) {
            return false;
        }
        try {
            $serialized = serialize($value);
            return $this->redis->set($key, $serialized, $ttl);
        } catch (Exception $e) {
            return false;
        }
    }

    public function delete($key) {
        if ($this->fallback || !$this->redis) {
            return false;
        }
        try {
            return $this->redis->del($key);
        } catch (Exception $e) {
            return false;
        }
    }

    public function flush() {
        if ($this->fallback || !$this->redis) {
            return false;
        }
        try {
            if (method_exists($this->redis, 'flushDB')) {
                return $this->redis->flushDB();
            } else {
                return $this->redis->flush();
            }
        } catch (Exception $e) {
            return false;
        }
    }
}

/**
 * SocketRedis
 * 
 * Minimal, lightweight socket-based Redis client fallback using standard RESP protocol.
 */
class SocketRedis {
    private $socket = null;
    private $connected = false;

    public function __construct($host, $port, $password = '', $db = 0) {
        $this->socket = @fsockopen($host, $port, $errno, $errstr, 2.0);
        if ($this->socket) {
            $this->connected = true;
            if ($password !== '') {
                $res = $this->execute(['AUTH', $password]);
                if (strpos($res, 'ERR') === 0 || strpos($res, 'NOAUTH') === 0) {
                    // Try ACL AUTH format (AUTH default <pass>)
                    $res2 = $this->execute(['AUTH', 'default', $password]);
                    if (strpos($res2, 'ERR') === 0) {
                        $this->connected = false;
                        @fclose($this->socket);
                        return;
                    }
                }
            }
            if ($db > 0) {
                $this->execute(['SELECT', (string)$db]);
            }
        }
    }

    public function isConnected() {
        return $this->connected;
    }

    public function get($key) {
        $res = $this->execute(['GET', $key]);
        return is_string($res) ? $res : false;
    }

    public function set($key, $value, $ttl = null) {
        if ($ttl) {
            $res = $this->execute(['SETEX', $key, (string)$ttl, $value]);
        } else {
            $res = $this->execute(['SET', $key, $value]);
        }
        return $res === 'OK';
    }

    public function del($key) {
        $res = $this->execute(['DEL', $key]);
        return $res !== false;
    }

    public function flush() {
        return $this->execute(['FLUSHDB']) === 'OK';
    }

    private function execute($args) {
        if (!$this->connected || !$this->socket) {
            return false;
        }
        
        $cmd = '*' . count($args) . "\r\n";
        foreach ($args as $arg) {
            $cmd .= '$' . strlen($arg) . "\r\n" . $arg . "\r\n";
        }
        
        @fwrite($this->socket, $cmd);
        return $this->readResponse();
    }

    private function readResponse() {
        $line = @fgets($this->socket);
        if ($line === false) {
            return false;
        }
        $line = trim($line);
        if ($line === '') return false;
        
        $type = $line[0];
        $payload = substr($line, 1);
        
        switch ($type) {
            case '+': // Status reply
                return $payload;
            case '-': // Error reply
                return 'ERR: ' . $payload;
            case ':': // Integer reply
                return intval($payload);
            case '$': // Bulk string reply
                $size = intval($payload);
                if ($size === -1) {
                    return null;
                }
                $data = '';
                while (strlen($data) < $size) {
                    $chunk = @fread($this->socket, $size - strlen($data));
                    if ($chunk === false) break;
                    $data .= $chunk;
                }
                @fgets($this->socket); // Consume CRLF at the end
                return $data;
            case '*': // Multi-bulk array reply
                $count = intval($payload);
                if ($count === -1) return null;
                $arr = [];
                for ($i = 0; $i < $count; $i++) {
                    $arr[] = $this->readResponse();
                }
                return $arr;
            default:
                return false;
        }
    }

    public function __destruct() {
        if ($this->socket) {
            @fclose($this->socket);
        }
    }
}

/**
 * Global cache invalidation helpers
 */
function clearUserCache($userId) {
    if (class_exists('RedisCache')) {
        $cache = RedisCache::getInstance();
        $cache->delete("user:profile:{$userId}");
        $cache->delete("user:folders:{$userId}");
        $cache->delete("user:stats:{$userId}");
        $cache->delete("user:filetype:{$userId}");
        
        $userVersion = (int)$cache->get("thoughts:user:version:{$userId}") ?: 1;
        $cache->set("thoughts:user:version:{$userId}", $userVersion + 1);
        
        $feedVersion = (int)$cache->get("thoughts:feed:version") ?: 1;
        $cache->set("thoughts:feed:version", $feedVersion + 1);
    }
}

function clearFolderCache($slug, $folderId = null, $userId = null) {
    if (class_exists('RedisCache')) {
        $cache = RedisCache::getInstance();
        $cache->delete("folder:slug:{$slug}");
        if ($folderId) {
            $cache->delete("folder:files:{$folderId}");
        }
        if ($userId) {
            $cache->delete("user:folders:{$userId}");
            $cache->delete("user:stats:{$userId}");
            $cache->delete("user:filetype:{$userId}");
        }
    }
}

<?php

namespace App\Repositories\Redis;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Str;

class Repository
{

    protected $table;
    protected $ttl;

    public function __construct()
    {
        $this->table = '';
        $this->ttl = null;
    }

    /**
     * Get ttl as seconds
     * @return int
     */
    public function ttl()
    {
        return $this->ttl;
    }

    /**
     * @return Cache
     */
    public function query()
    {
        return app('cache');
    }

    /**
     * @return Connection
     */
    public function connection()
    {
        $conn = app('redis.connection');
        $conn->setEventDispatcher(app('events'));

        return $conn;
    }

    /**
     * Make key by item name and value
     *
     * @param string $name
     * @param string $value
     * @return string
     */
    public function key($name, $value)
    {
        return Str::slug(env('APP_NAME'), '_') . ':' . $this->table . ':' . $name . '.' . $value;
    }

    /**
     * @param string $method
     * @param array $params
     *
     * @return any
     */
    public function command($method, array $params)
    {
        return $this->connection()->command($method, $params);
    }

    /**
     * @param string $key
     * @param any $value
     * @param int $ttl as seconds
     *
     * @return bool
     */
    public function set($key, $value, $ttl = 0)
    {
        $conn = $this->connection();

        $stream = serialize($value);

        $result = $conn->command('set', [$key, $stream]);

        if ($ttl) {
            $conn->command('expire', [$key, $ttl]);
        }

        return $result;
    }

    /**
     * @param array $values
     * @param int $ttl as seconds
     *
     * @return bool
     */
    public function setMultiple($values, $ttl = 0)
    {
        $conn = $this->connection();

        $stream = [];

        foreach ($values as $key => $value) {
            $stream[$key] = serialize($value);
        }

        $result = $conn->command('mset', [$stream]);

        if ($ttl) {
            foreach ($values as $key => $value) {
                $conn->command('expire', [$key, $ttl]);
            }
        }

        return $result;
    }

    /**
     * @param string $key
     * @param any $default
     *
     * @return any
     */
    public function get($key, $default = null)
    {
        $conn = $this->connection();

        $stream = $conn->command('get', [$key]);

        $value = unserialize($stream);

        return $value ?: $default;
    }

    /**
     * @param array $keys
     *
     * @return array
     */
    public function getMultiple($keys)
    {
        $conn = $this->connection();

        $stream = $conn->command('mget', [$keys]);

        $values = [];

        for ($i = 0; $i < count($keys); $i++) {
            $values[$keys[$i]] = unserialize($stream[$i]) ?: null;
        }

        return $values;
    }

    /**
     * Purge cache by key. If key equal to null purge all cache.
     *
     * @param string $key
     *
     * @return void
     */
    public function purge($key = null)
    {
        $conn = $this->connection();

        if (is_null($key)) {
            $keys = $conn->command('keys', ['*' . $this->table . ':*']);
        } else {
            $keys = is_array($key) ? $key : [$key];
        }

        if (!empty($keys)) {
            $conn->command('del', $keys);
        }
    }


    /**
     * Purge cache by key match. use like
     *
     * @param string $key
     *
     * @return void
     */
    public function purgeByKeyMatch($key = null)
    {
        $conn = $this->connection();

        $keys = $conn->client()->keys("*$key*");

        if (!empty($keys)) {
            $conn->command('del', $keys);
        }
    }
}

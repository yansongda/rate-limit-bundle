<?php

namespace Yansongda\RateLimitBundle;

use Predis\Client as Predis;
use Snc\RedisBundle\Client\Phpredis\Client;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @author yansongda <me@yansongda.cn>
 *
 * @property int $count
 * @property int $limit
 * @property int $period
 * @property int $reset_time
 */
class RateLimit
{
    /**
     * request.
     *
     * @var \Symfony\Component\HttpFoundation\Request|null
     */
    protected $request;

    /**
     * redis.
     *
     * @var Predis|Client
     */
    protected $redis;

    /**
     * info.
     *
     * @var array
     */
    protected $info = [
        'limit'      => 60,
        'period'     => 60,
        'count'      => 0,
        'reset_time' => 0,
    ];

    /**
     * Bootstrap.
     *
     * @author yansongda <me@yansongda.cn>
     *
     * @param RequestStack  $request_stack
     * @param Client|Predis $redis
     */
    public function __construct(RequestStack $request_stack, $redis)
    {
        $this->request = $request_stack->getMasterRequest();
        $this->redis = $redis;
    }

    /**
     * __get.
     *
     * @author yansongda <me@yansongda.cn>
     *
     * @param $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * isBlocked.
     *
     * @author yansongda <me@yansongda.cn>
     *
     * @param string|null $key
     * @param int         $limit
     * @param int         $period
     *
     * @return bool
     */
    public function isBlocked(?string $key = null, int $limit = 60, int $period = 60): bool
    {
        if ($limit === -1) {
            return false;
        }

        $now = microtime(true) * 1000;
        $key = $key ?? $this->getDefaultKey();

        $this->redis->zremrangebyscore($key, 0, $now - $period * 1000);

        $this->info['limit'] = $limit;
        $this->info['period'] = $period;
        $this->info['count'] = $this->redis->zcount($key, $now - $period * 1000, $now);
        $this->info['reset_time'] = $this->getResetTime($key, $now);

        if ($this->info['count'] < $limit) {
            $this->redis->zadd($key, $now, $now);

            return false;
        }

        return true;
    }

    /**
     * getDefaultKey.
     *
     * @author yansongda <me@yansongda.cn>
     *
     * @return string
     */
    public function getDefaultKey()
    {
        return $this->request->getClientIp().':'.
            $this->request->attributes->get('_route', 'default');
    }

    /**
     * getResetTime.
     *
     * @author yansongda <me@yansongda.cn>
     *
     * @param $key
     * @param $now
     *
     * @return int
     */
    public function getResetTime($key, $now): int
    {
        $data = $this->redis->zrangebyscore($key, $now - $this->info['period'] * 1000, $now, ['limit' => [0, 1]]);

        if (count($data) === 0) {
            return $this->info['reset_time'] = time() + $this->info['period'];
        }

        return intval($data[0] / 1000) + $this->info['period'];
    }

    /**
     * get.
     *
     * @author yansongda <me@yansongda.cn>
     *
     * @param      $key
     * @param null $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (isset($this->info[$key])) {
            return $this->info[$key];
        }

        return $default;
    }
}

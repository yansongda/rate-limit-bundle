<?php

namespace Yansongda\RateLimitBundle\Annotation;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationAnnotation;
use Yansongda\RateLimitBundle\Exceptions\InvalidParamsException;

/**
 * @author yansongda <me@yansongda.cn>
 *
 * @Annotation
 * @Target({"METHOD", "CLASS"})
 *
 * @property array $methods
 * @property int $limit
 * @property int $period
 * @property array $custom
 */
class Throttle extends ConfigurationAnnotation
{
    /**
     * Http methods.
     *
     * @var array
     */
    protected $methods = [];

    /**
     * Limit.
     *
     * @var int
     */
    protected $limit = 60;

    /**
     * period.
     *
     * @var int
     */
    protected $period = 60;

    /**
     * Custom limit and period.
     *
     * @var array
     */
    protected $custom = [];

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
     * __set.
     *
     * @author yansongda <me@yansongda.cn>
     *
     * @param $key
     * @param $value
     *
     * @return void
     */
    public function __set($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * __call.
     *
     * @author yansongda <me@yansongda.cn>
     *
     * @param $method
     * @param $params
     *
     * @throws InvalidParamsException
     *
     * @return mixed|null
     */
    public function __call($method, $params)
    {
        $purpose = substr($method, 0, 3);

        if ($purpose === 'get') {
            return $this->get(lcfirst(substr($method, 3)));
        }

        throw new InvalidParamsException();
    }

    /**
     * Returns the alias name for an annotated configuration.
     *
     * @return string
     */
    public function getAliasName()
    {
        return 'throttle';
    }

    /**
     * Returns whether multiple annotations of this type are allowed.
     *
     * @return bool
     */
    public function allowArray()
    {
        return true;
    }

    /**
     * setMethods.
     *
     * @author yansongda <me@yansongda.cn>
     *
     * @param $methods
     *
     * @return void
     */
    public function setMethods($methods)
    {
        $this->methods = (array) $methods;
    }

    /**
     * setLimit.
     *
     * @author yansongda <me@yansongda.cn>
     *
     * @param $limit
     *
     * @return void
     */
    public function setLimit($limit)
    {
        $this->limit = intval($limit);
    }

    /**
     * setPeriod.
     *
     * @author yansongda <me@yansongda.cn>
     *
     * @param $period
     *
     * @return void
     */
    public function setPeriod($period)
    {
        $this->period = intval($period);
    }

    /**
     * setCustom.
     *
     * @author yansongda <me@yansongda.cn>
     *
     * @param array $custom
     *
     * @return void
     */
    public function setCustom(array $custom)
    {
        $this->custom = $custom;
    }

    /**
     * get.
     *
     * @author yansongda <me@yansongda.cn>
     *
     * @param      $key
     * @param null $default
     *
     * @return mixed|null
     */
    public function get($key, $default = null)
    {
        if (property_exists($this, $key)) {
            return $this->{$key};
        }

        return $default;
    }

    /**
     * set.
     *
     * @author yansongda <me@yansongda.cn>
     *
     * @param $key
     * @param $value
     *
     * @return void
     */
    public function set($key, $value)
    {
        $method = 'set';

        foreach (explode('_', $key) as $item) {
            $method .= ucfirst($item);
        }

        if (method_exists($this, $method)) {
            $this->{$method}($value);
        }
    }

    /**
     * toArray.
     *
     * @author yansongda <me@yansongda.cn>
     * @throws \ReflectionException
     *
     * @return array
     */
    public function toArray()
    {
        $result = [];

        foreach ((new \ReflectionClass($this))->getProperties() as $item) {
            $result[$item->getName()] = $this->{$item->getName()};
        }

        return $result;
    }
}

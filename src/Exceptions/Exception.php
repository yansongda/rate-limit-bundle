<?php

namespace Yansongda\RateLimitBundle\Exceptions;

use Throwable;

class Exception extends \Exception
{
    const INVALID_PARAMS = 1000;

    const INVALID_CONFIG = 1001;

    /**
     * raw.
     *
     * @var array
     */
    public $raw = [];

    /**
     * Bootstrap.
     *
     * @author yansongda <me@yansongda.cn>
     *
     * @param string         $message
     * @param array|string   $raw
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = '', int $code = 0, $raw = [], Throwable $previous = null)
    {
        $this->raw = is_array($raw) ? $raw : [$raw];

        parent::__construct($message, $code, $previous);
    }
}

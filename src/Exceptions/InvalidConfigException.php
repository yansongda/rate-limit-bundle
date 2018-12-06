<?php

namespace Yansongda\RateLimitBundle\Exceptions;

use Throwable;

class InvalidConfigException extends Exception
{
    /**
     * Bootstrap.
     *
     * @author yansongda <me@yansongda.cn>
     *
     * @param string         $message
     * @param array          $raw
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = '', int $code = Exception::INVALID_CONFIG, $raw = [], ?Throwable $previous = null)
    {
        $message = $message === '' ? 'Invalid Configs' : $message;

        parent::__construct($message, $code, $raw, $previous);
    }
}

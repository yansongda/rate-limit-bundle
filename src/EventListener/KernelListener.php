<?php

namespace Yansongda\RateLimitBundle\EventListener;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Yansongda\RateLimitBundle\Annotation\Throttle;
use Yansongda\RateLimitBundle\DependencyInjection\Configuration;
use Yansongda\RateLimitBundle\Exceptions\InvalidParamsException;
use Yansongda\RateLimitBundle\RateLimit;

class KernelListener implements EventSubscriberInterface
{
    /**
     * container.
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * service.
     *
     * @var RateLimit
     */
    protected $kernel;

    /**
     * Bootstrap.
     *
     * @author yansongda <me@yansongda.cn>
     *
     * @param ContainerInterface $container
     * @param RateLimit          $kernel
     */
    public function __construct(ContainerInterface $container, RateLimit $kernel)
    {
        $this->container = $container;
        $this->kernel = $kernel;
    }

    /**
     * getSubscribedEvents.
     *
     * @author yansongda <me@yansongda.cn>
     *
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => [
                ['throttle', -1]
            ],
            KernelEvents::RESPONSE => [
                ['addThrottleHeader', 0]
            ]
        ];
    }

    /**
     * throttle.
     *
     * @author yansongda <me@yansongda.cn>
     *
     * @param FilterControllerEvent $event
     *
     * @throws InvalidParamsException
     *
     * @return void
     */
    public function throttle(FilterControllerEvent $event): void
    {
        $throttle = $this->getMostSuitableThrottle($event->getRequest());

        if (is_null($throttle)) {
            return;
        }

        list($limit, $period) = $this->getLimitPeriod($throttle, $event->getRequest());

        if (!$this->kernel->isBlocked(null, $limit, $period)) {
            return;
        }

        $response = $this->container->getParameter('yansongda_rate_limit.response');

        if ($response['exception']) {
            throw new $response['exception']($response['message'], $response['code']);
        }

        $event->setController(function () use ($response) {
            if ($response['code'] > 400 && $response['code'] < 499) {
                return new Response($response['message'], $response['code']);
            }

            return new Response($response['message'], Configuration::HTTP_TOO_MANY_REQUEST);
        });
        $event->stopPropagation();
    }

    /**
     * addThrottleHeader.
     *
     * @author yansongda <me@yansongda.cn>
     *
     * @param FilterResponseEvent $event
     *
     * @return void
     */
    public function addThrottleHeader(FilterResponseEvent $event): void
    {
        $throttle = $this->getMostSuitableThrottle($event->getRequest());

        if (!$this->container->getParameter('yansongda_rate_limit.display_headers') ||
            is_null($throttle)) {
            return;
        }

        $headers = $this->container->getParameter('yansongda_rate_limit.headers');

        $event->getResponse()->headers->set($headers['limit'], $this->kernel->limit);
        $event->getResponse()->headers->set($headers['reset'], $this->kernel->reset_time);
        $event->getResponse()->headers->set(
            $headers['remaining'],
            $this->kernel->limit - $this->kernel->count
        );
    }

    /**
     * Get limit and period.
     *
     * @author yansongda <me@yansongda.cn>
     *
     * @param Throttle $throttle
     *
     * @param Request  $request
     *
     * @throws InvalidParamsException
     * @return array
     */
    public function getLimitPeriod(Throttle $throttle, Request $request): array
    {
        if (count($throttle->custom) === 0) {
            return [$throttle->limit, $throttle->period];
        }

        if (count($throttle->custom) !== 2) {
            throw new InvalidParamsException('[Yansongda-Rate-Limit-Bundle] Custom Must Be An Array Which Have Two Elements');
        }

        list($class, $method) = $throttle->custom;

        if (!class_exists($class)) {
            throw new InvalidParamsException("[Yansongda-Rate-Limit-Bundle] Class [{$class}] Not Exist");
        }

        $custom = new $class;

        if (!method_exists($custom, $method)) {
            throw new InvalidParamsException("[Yansongda-Rate-Limit-Bundle] Method [{$method}] Not Exist");
        }

        $data = $custom->{$method}($request);

        if (is_null($data) || !is_array($data) || count($data) !== 2 ||
            !is_int($data[0] ?? 'default') || !is_int($data[1] ?? 'default')) {
            throw new InvalidParamsException('[Yansongda-Rate-Limit-Bundle] Custom Function Must Return Array With Int Limit And Int Period');
        }

        return $data;
    }

    /**
     * getMostSuitableThrottle.
     *
     * @author yansongda <me@yansongda.cn>
     *
     * @param Request    $request
     *
     * @return Throttle|null
     */
    protected function getMostSuitableThrottle(Request $request): ?Throttle
    {
        if (!$this->container->getParameter('yansongda_rate_limit.enable')) {
            return null;
        }

        $throttles = $request->attributes->get('_throttle', []);
        $method = $request->getMethod();

        $default = null;
        $most_suitable = null;

        foreach ($throttles as $throttle) {
            if (count($throttle->methods) === 0) {
                $default = $throttle;
            } elseif (in_array($method, $throttle->methods)) {
                $most_suitable = $throttle;
            }
        }

        return $most_suitable ?? $default;
    }
}

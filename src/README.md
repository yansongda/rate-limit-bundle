<h1 align="center">Rate-Limit-Bundle</h1>

主要提供了路由限流功能，限流的参数可由自定义函数动态进行控制

## 运行环境
- php 7.1+
- symfony 4
- composer 
- redis

## 功能
- 静态限流
- 动态限流
- 根据 ip + 路由 进行限制

## 安装

`composer require yansongda/rate-limit-bundle -vvv`

## 概要

根据 IP + 路由名 进行确定特定的客户端，通过 redis 进行限流记录。

## 使用

### 静态限流

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Yansongda\RateLimitBundle\Annotation\Throttle;

class HomeController extends AbstractController
{
    /**
     * 静态限流： 同一个 IP 访问 test 路由，60 秒内只能访问 2 次.
     *
     * @author yansongda <me@yansongda.cn>
     *
     * @Route("/test", name="test")
     * @Throttle(limit=2, period=60)
     * 
     * @return mixed
     */
    public function testAction()
    {
        return JsonResponse::create(['code' => 0]);
    }
}
```

### 动态限流

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Yansongda\RateLimitBundle\Annotation\Throttle;

class HomeController extends AbstractController
{
    /**
     * 动态限流：具体的 limit 及 period 参数由 custom 返回.
     *
     * @author yansongda <me@yansongda.cn>
     *
     * @Route("/test", name="token")
     * @Throttle(limit=2, period=60, custom={"App\Throttles\CustomLimitPeriod", "token"})
     * 
     * @return mixed
     */
    public function tokenAction()
    {
        return JsonResponse::create(['code' => 0]);
    }
}
```

```php
<?php

namespace App\Throttles;

use Symfony\Component\HttpFoundation\Request;

class CustomLimitPeriod
{
    /**
     * 自定义限流策略.
     *
     * @author yansongda <me@yansongda.cn>
     * @param Request $request 此参数回调时自动载入
     *
     * @return array
     */
    public function token(Request $request)
    {
        // 返回的数据中，第一个为 limit，第二个为 period，必须为 int 类型。如果 limit 返回 -1 则无限制
        return [20, 60];
    }
}
```

## 配置

下面是默认的配置信息。如果需要更改，在 config 的 packages 目录下新建一个 yml 文件，然后复制以下内容更改即可。

```yaml
yansongda_rate_limit:
    # 是否开启
    enable: true
    
    # snc_redis 客户端
    redis_client: default
    
    # 是否展示 headers
    display_headers: true
    
    # headers 的 key
    headers:
        limit: X-RateLimit-Limit
        remaining: X-RateLimit-Remaining
        reset: X-RateLimit-Reset
        
    # response 内容，如果 exception 不为 null，则默认抛出写入的 exception
    response:
        message: 'Out Of Limit'
        code: 429
        exception: null
```

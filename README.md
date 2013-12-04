SlmCache
========

[![Build Status](https://travis-ci.org/juriansluiman/SlmCache.png?branch=master)](https://travis-ci.org/juriansluiman/SlmCache)
[![Latest Stable Version](https://poser.pugx.org/slm/cache/v/stable.png)](https://packagist.org/packages/slm/cache)

Version 0.1.0 Created by Jurian Sluiman

Requirements
------------
* [Zend Framework 2](https://github.com/zendframework/zf2)

Introduction
------------


Installation
------------

SlmCache works with Composer. To install it, just add the following line into your `composer.json` file:

```json
"require": {
    "slm/cache": "dev-master"
}
```

Documentation
-------------
SlmCache works with a configured cache storage adapter and a list of routes which can be cached. Based on the request and the matched route SlmCache will fetch the response from the cache or not.

### Configure cache storage
The cache can be configured in two ways. The first method uses the cache storage factory to instantiate a new cache instance. The second method let you point to a service in the service locator to fetch an existing cache service.

In below example, all configuration inside `cache` will be injected into the `Zend\Cache\StorageFactory::factory()` method. This enables you to configure the adapter, all adapter options and if needed, plugins.

```php
'slm_cache' => array(
    'cache'  => array(
        'adapter' => array(
            'name'    => 'apc',
            'options' => array('ttl' => 3600),
        ),
    ),
),
```

In this example, the configuration is simply a string and points to a service. This enables you to have a single cache service in your application which can be used for other things than only SlmCache.

```php
'slm_cache' => array(
    'cache'  => 'my-cache-adapter'
),
```

### Configure cache prefix
Cache prefix is used to namespace cached data so it will not conflict with other modules. Ideally it should be unique.

```php
'slm_cache' => array(
    'cache_prefix' => 'my_cache_prefix_',
);
```

In case you don't specify a cache_prefix, SlmCache will default to 'slm_cache_'.

### Configure routes to be cached
The routes which can be cached must be configured in a single array. SlmCache will match the currently matched route name to this list of routes. If there is a match, the caching mechanism will be enabled. This allows you to have non-cached and cached routes inside a single application.

If the SlmCache is triggered, the cache will be used to fetch the response body. With short-circuiting the event system of Zend Framework 2, this result is directly returned to the browser. This will bypass the complete `EVENT_DISPATCH` part of the application execution. If there is no hit on the cache, SlmCache will wait until the application has finished rendering the complete response. This response is stored in the cache so next time a request takes place, the cache has a hit.

To configure a route to be cached, set it inside the `routes` array:

```php
'slm_cache' => array(
    'routes'  => array(
        'home'  => array(),
        'about' => array(),
    ),
),
```

### Match only routes for some HTTP methods
There is the possibility you have a route which is used for both GET and POST. You can configure SlmCache to only keep a cached version of the GET version and not cache the POST. Use the array to set the `match_method`:

```php
'slm_cache' => array(
    'routes'  => array(
        'contact' => array('match_method' => 'GET'),
    ),
),
```

You can also match multiple methods, if you need to:

```php
'slm_cache' => array(
    'routes'  => array(
        'contact' => array('match_method' => 'GET', 'HEAD'),
    ),
),
```

### Match only routes with specified route parameters
If you have a segment route where a part in the route sets the action, you might want to only cache a specific action. For the route `foo[/:action]` you might want to cache the match where `action` is `bar` but not for the `action` equals to `baz`. Use the `match_route_params` flag to configure this filtering:

```php
'slm_cache' => array(
    'routes'  => array(
        'foo' => array(
            'match_route_params' => array('action' => 'bar')
        ),
    ),
),
```

You can have multiple actions you allow, but not all. The value can be an array of possible actions:

```php
'slm_cache' => array(
    'routes'  => array(
        'foo' => array(
            'match_route_params' => array('action' => array('bar', 'baz'))
        ),
    ),
),
```

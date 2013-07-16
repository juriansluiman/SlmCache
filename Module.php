<?php
/**
 * Copyright (c) 2013 Jurian Sluiman.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the names of the copyright holders nor the names of the
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @author      Jurian Sluiman <jurian@juriansluiman.nl>
 * @copyright   2013 Jurian Sluiman.
 * @license     http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link        http://juriansluiman.nl
 */

namespace SlmCache;

use Zend\EventManager\EventInterface;

use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;

use Zend\Http\Response;

use Zend\Cache\StorageFactory;
use Zend\Cache\Storage\StorageInterface;

class Module
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function onBootstrap(EventInterface $e)
    {
        $app = $e->getApplication();
        $em  = $app->getEventManager();

        $em->attach(MvcEvent::EVENT_ROUTE, array($this, 'checkRoute'));
        $em->attach(MvcEvent::EVENT_FINISH, array($this, 'saveRoute'));
    }

    public function checkRoute(MvcEvent $e)
    {
        $match = $e->getRouteMatch();
        if (!$match instanceof RouteMatch) {
            return;
        }
        $route  = $match->getMatchedRouteName();
        $config = $e->getApplication()->getServiceManager()->get('Config');
        $routes = $config['slm_cache']['routes'];

        if (!array_key_exists($route, $routes)) {
            return;
        }

        $result = $this->getFromCache($e, $route, $routes[$route]);
        if (!$result instanceof Response) {
            return;
        }

        return $result;
    }

    public function saveRoute(MvcEvent $e)
    {

    }

    protected function getFromCache(MvcEvent $e, $key, array $config = array())
    {
        $cache = $this->getCache($e);
        if ($result = $cache->getItem($key)) {
            $response = $e->getResponse();
            $response->setBody($result);

            $e->setParam('cached', true);

            return $response;
        }
    }

    protected function getCache(MvcEvent $e)
    {
        $sm     = $e->getApplication()->getServiceManager();
        $config = $sm->get('Config');
        $config = $config['slm_cache']['cache'];

        if (is_string($config)) {
            $cache = $sm->get($config);
        } elseif (is_array($config)) {
            $cache = StorageFactory::factory($config);
        } else {
            throw new \Exception('Cache must be configured');
        }

        if (!$cache instanceof StorageInterface) {
            throw new \Exception('Cache is no instance of storage interface!');
        }

        return $cache;
    }
}
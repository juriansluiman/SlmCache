<?php
/**
 * Copyright (c) 2012-2013 Jurian Sluiman.
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
 * @copyright   2012-2013 Jurian Sluiman.
 * @license     http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link        http://juriansluiman.nl
 */
namespace SlmCacheTest\Listener;

use PHPUnit_Framework_TestCase as TestCase;
use PHPUnit_Framework_Assert;

use SlmCache\Listener\Cache as CacheListener;

use Zend\EventManager\EventManager;
use Zend\ServiceManager\ServiceManager;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;

class CacheTest extends TestCase
{
    public function testMatchRouteIsTriggered()
    {
        $sl   = new ServiceManager;
        $sl->setService('Config', array());

        $mock = $this->getMock('SlmCache\Listener\Cache', array('matchRoute'), array($sl));
        $mock->expects($this->once())
             ->method('matchRoute');

        $em = new EventManager;
        $mock->attach($em);

        $event = new MvcEvent;
        $event->setName(MvcEvent::EVENT_ROUTE);
        $em->trigger($event);
    }

    public function testSaveRouteIsTriggered()
    {
        $sl   = new ServiceManager;
        $sl->setService('Config', array());

        $mock = $this->getMock('SlmCache\Listener\Cache', array('saveRoute'), array($sl));
        $mock->expects($this->once())
             ->method('saveRoute');

        $em = new EventManager;
        $mock->attach($em);

        $event = new MvcEvent;
        $event->setName(MvcEvent::EVENT_FINISH);
        $em->trigger($event);
    }

    public function testMatchReturnsNullForMissingRouteMatch()
    {
        $sl = new ServiceManager;
        $sl->setService('Config', array());

        $listener = new CacheListener($sl);

        $event  = new MvcEvent;
        $result = $listener->matchRoute($event);

        $this->assertNull($result);
    }

    public function testMatchReturnsNullForMissingKey()
    {
        $sl = new ServiceManager;
        $sl->setService('Config', array(
            'slm_cache' => array(
                'routes' => array()
            ),
        ));
        $listener = new CacheListener($sl);

        $event = new MvcEvent;
        $event->setRouteMatch(new RouteMatch(array()));
        $result = $listener->matchRoute($event);

        $this->assertNull($result);
    }

    public function testSuccessfulMatchInvokesCacheRetrieval()
    {
        $sl = new ServiceManager;
        $sl->setService('Config', array(
            'slm_cache' => array(
                'routes' => array(
                    'home' => array()
                ),
            ),
        ));

        $mock = $this->getMock('SlmCache\Listener\Cache', array('fromCache'), array($sl));
        $mock->expects($this->once())
            ->method('fromCache');

        $match = new RouteMatch(array());
        $match->setMatchedRouteName('home');

        $event = new MvcEvent;
        $event->setRouteMatch($match);

        $mock->matchRoute($event);
    }

    public function testCachePrefixIsUserDefined()
    {
        $config = array(
            'slm_cache' => array(
                'cache_prefix' => 'my_cache_prefix'
            )
        );

        $sl = new ServiceManager;
        $sl->setService('Config', $config);

        $listener = new CacheListener($sl);

        $this->assertEquals(
            $config['slm_cache']['cache_prefix'],
            PHPUnit_Framework_Assert::readAttribute($listener, 'cache_prefix')
        );
    }

    public function testCachePrefixIsDefault()
    {
        $config = array(
            'slm_cache' => array(
                'cache' => array(),
            )
        );

        $sl = new ServiceManager;
        $sl->setService('Config', $config);

        $listener = new CacheListener($sl);

        $this->assertEquals(
            'slm_cache_',
            PHPUnit_Framework_Assert::readAttribute($listener, 'cache_prefix')
        );
    }
}
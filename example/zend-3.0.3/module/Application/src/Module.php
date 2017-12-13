<?php
/**
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 *
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application;

use Zend\Mvc\MvcEvent;

class Module
{
    const VERSION = '3.0.3-dev';

    public function getConfig()
    {
        return include __DIR__.'/../config/module.config.php';
    }

    public function onBootstrap(\Zend\Mvc\MvcEvent $event)
    {
        $bugsnag = $GLOBALS['bugsnag'];
        $sharedManager = $event->getApplication()->getEventManager()->getSharedManager();
        $sharedManager->attach('Zend\Mvc\Application', 'dispatch.error', function ($exception) use ($bugsnag) {
            if ($exception->getParam('exception')) {
                $bugsnag->notifyException($exception->getParam('exception'));

                return false;
            }
        });
    }
}

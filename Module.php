<?php
namespace Zf2Db;

use Zend\Mvc\ModuleRouteListener;

class Module
{
    public function onBootstrap($event)
    {
        $app = $event->getApplication();
        $events = $app->getEventManager();
        $services = $app->getServiceManager();
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }
}

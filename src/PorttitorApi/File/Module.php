<?php
namespace PorttitorApi\File;

use ZF\Apigility\Provider\ApigilityProviderInterface;
use Zend\Uri\UriFactory;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\ModuleRouteListener;
use ZF\MvcAuth\MvcAuthEvent;

class Module implements ApigilityProviderInterface
{
    public function onBootstrap(MvcEvent $mvcEvent)
    {
        UriFactory::registerScheme('chrome-extension', 'Zend\Uri\Uri');
        $serviceManager = $mvcEvent->getApplication()->getServiceManager();
        $eventManager   = $mvcEvent->getApplication()->getEventManager();
        $sharedEventManager = $eventManager->getSharedManager();
        // attach image shared event listener
        $sharedEventManager->attachAggregate($serviceManager->get('PorttitorApi\\File\\SharedEventListener'));
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
        // set role based on oAuth client id
        $eventManager->attach(
            MvcAuthEvent::EVENT_AUTHENTICATION_POST,
            function ($mvcAuthEvent) {
                $identity     = $mvcAuthEvent->getIdentity();
                $authIdentity = $identity->getAuthenticationIdentity();
                if (!$identity instanceof \ZF\MvcAuth\Identity\GuestIdentity) {
                    $identity->setName($authIdentity['client_id']);
                }
            },
            100
        );
        // attach ACL for checking Scope
        $eventManager->attach(
            MvcAuthEvent::EVENT_AUTHORIZATION_POST,
            $serviceManager->get('PorttitorApi\\File\\Authorization\\AclScopeListener'),
            101
        );
        // attach ACL for checking File Owner
        $eventManager->attach(
            MvcAuthEvent::EVENT_AUTHORIZATION_POST,
            $serviceManager->get('PorttitorApi\\File\\Authorization\\AclFileListener'),
            100
        );
    }
    
    public function getConfig()
    {
        return include __DIR__ . '/../../../config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            'ZF\Apigility\Autoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__,
                ),
            ),
        );
    }
}

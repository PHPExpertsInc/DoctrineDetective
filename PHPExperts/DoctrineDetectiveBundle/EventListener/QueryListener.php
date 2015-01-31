<?php

namespace PHPExperts\DoctrineDetectiveBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use PHPExperts\DoctrineDetectiveBundle\Services\QueryLogger;

class QueryListener
{
    /** @var EntityManager */
    protected $em;

    /** @var QueryLogger */
    protected $queryLogger;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    protected function getRequestActionName($request)
    {
        return $request->attributes->get('_controller');
//        $actionParams = explode('::', $request->attributes->get('_controller'));
//
//        // Strip the word 'Controller' from the controller name.
//        $controllerName = substr($actionParams[0], 0, -10);
//        // Strip the word 'Action' from the action name.
//        $actionName = substr($actionParams[1], 0, -6);
//
//        return "$controllerName::$actionName";
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        $actionName = $this->getRequestActionName($event->getRequest());
        $this->queryLogger = new QueryLogger($actionName);
        $this->em->getConnection()->getConfiguration()->setSQLLogger(
            $this->queryLogger
        );
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (!$this->queryLogger) {
            return;
        }

        $request = $event->getRequest();

        // Only do something when the requested format is "json".
        if ($request->getRequestFormat() != 'json') {
            return;
        }

        $sqlLog = $this->queryLogger->getLog();

        $response = $event->getResponse();
        $content = json_decode($response->getContent(), true) + [ 'sqlLog' => $sqlLog ];
        $response->setContent(json_encode($content));
        $event->setResponse($response);

        $foo = 1;
    }
}

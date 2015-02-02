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
    }
}

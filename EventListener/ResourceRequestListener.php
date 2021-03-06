<?php

namespace Galmi\XacmlBundle\EventListener;

use Doctrine\Common\Annotations\Reader;
use Galmi\Xacml\Request as XacmlRequest;
use Galmi\XacmlBundle\Annotations\XacmlResource;
use Galmi\XacmlBundle\Xacml\Resource;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class ResourceRequestListener
{
    /** @var string */
    private $category = 'Resource';

    /** @var XacmlRequest */
    private $xacmlRequest;

    /** @var Reader */
    private $annotationsReader;

    /**
     * ResourceRequestListener constructor.
     * @param XacmlRequest $xacmlRequest
     * @param Reader $annotationsReader
     */
    public function __construct(XacmlRequest $xacmlRequest, Reader $annotationsReader)
    {
        $this->xacmlRequest = $xacmlRequest;
        $this->annotationsReader = $annotationsReader;
    }

    /**
     * Add resource information for request from annotations
     *
     * @param GetResponseEvent $request
     */
    public function onKernelRequest(GetResponseEvent $request)
    {
        $controller = $request->getRequest()->get('_controller');
        $controllerParts = explode('::', $controller);
        if (is_array($controllerParts) && count($controllerParts) == 2) {
            $class = $controllerParts[0];
            $method = $controllerParts[1];
            $object = new \ReflectionMethod($class, $method);
            $resources = [];
            foreach ($this->annotationsReader->getMethodAnnotations($object) as $configuration) {
                if ($configuration instanceof XacmlResource) {
                    $baseClassName = $this->getBaseClassName($configuration->entity);
                    $resources[$baseClassName] = new Resource(
                        $configuration->entity,
                        $request->getRequest()->get($configuration->id),
                        $configuration->method
                    );
                    $resources['type'] = $baseClassName;
                }
            }
            if (!empty($resources)) {
                $this->xacmlRequest->set($this->category, $resources);
            }
        }
    }

    /**
     * Return short name of class name with namespace
     *
     * @param $fullName
     * @return string
     */
    private function getBaseClassName($fullName)
    {
        return substr(strrchr($fullName, '\\'), 1);
    }
}
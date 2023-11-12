<?php

declare(strict_types=1);

namespace araise\CrudBundle\Routing;

use araise\CrudBundle\Enums\Page;
use araise\CrudBundle\Manager\DefinitionManager;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class CrudLoader extends Loader
{
    // TODO: add patterns to support other versions e.g. ulid as well.
    private const URL_GENERATOR_ENTITY_KEY_REQUIREMENT_EXPRESSION = '[a-fA-F0-9]{8}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{12}|\d+';

    private bool $isLoaded = false;

    public function __construct(
        protected DefinitionManager $definitionManager
    ) {
        parent::__construct();
    }

    public function load(mixed $resource, mixed $type = null): RouteCollection
    {
        if ($this->isLoaded) {
            throw new \RuntimeException('Do not add the "araise_crud" loader twice');
        }

        $routes = new RouteCollection();

        foreach ($this->definitionManager->getDefinitions() as $definition) {
            foreach ($definition::getCapabilities() as $capability) {
                if ($capability instanceof Page) {
                    $route = new Route(
                        '/'.$definition::getRoutePathPrefix().'/',
                        [
                            '_resource' => $resource,
                            '_controller' => $definition::getController().'::'.$capability->toRoute().'Action',
                        ]
                    );

                    switch ($capability) {
                        case Page::INDEX:
                            break;
                        case Page::SHOW:
                            $route->setPath($route->getPath().'{id}');
                            $route->setRequirement('id', self::URL_GENERATOR_ENTITY_KEY_REQUIREMENT_EXPRESSION);
                            break;
                        case Page::RELOAD:
                            $route->setPath($route->getPath().'{id}/reload/{block}/{field?}');
                            $route->setRequirement('id', self::URL_GENERATOR_ENTITY_KEY_REQUIREMENT_EXPRESSION);
                            $route->setRequirement('block', '\w+');
                            $route->setRequirement('field', '(\w|\.)+');
                            break;
                        case Page::CREATE:
                            $route->setPath($route->getPath().'create');
                            $route->setMethods(['GET', 'POST']);
                            break;
                        case Page::CREATEMODAL:
                            $route->setPath($route->getPath().'createmodal');
                            $route->setMethods(['GET', 'POST']);
                            break;
                        case Page::EDIT:
                            $route->setPath($route->getPath().'{id}/edit');
                            $route->setMethods(['GET', 'POST', 'PUT', 'PATCH']);
                            $route->setRequirement('id', self::URL_GENERATOR_ENTITY_KEY_REQUIREMENT_EXPRESSION);
                            break;
                        case Page::DELETE:
                            $route->setPath($route->getPath().'{id}/delete');
                            $route->setMethods(['POST']);
                            $route->setRequirement('id', self::URL_GENERATOR_ENTITY_KEY_REQUIREMENT_EXPRESSION);
                            break;
                        case Page::BATCH:
                            $route->setPath($route->getPath().'batch');
                            $route->setMethods(['POST']);
                            break;
                        case Page::EXPORT:
                            $route->setPath($route->getPath().'export');
                            $route->setMethods(['GET']);
                            break;
                        case Page::AJAXFORM:
                            $route->setPath($route->getPath().'ajax-form');
                            $route->setMethods(['POST']);
                            break;
                        case Page::JSONSEARCH:
                            $route->setPath($route->getPath().'json-search');
                            $route->setMethods(['GET']);
                            break;
                    }

                    $routes->add($definition::getRoutePrefix().'_'.$capability->toRoute(), $route);
                }
            }
        }

        $this->isLoaded = true;

        return $routes;
    }

    /**
     * @return bool
     */
    public function supports(mixed $resource, mixed $type = null)
    {
        return $type === 'araise_crud';
    }
}

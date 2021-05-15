<?php
declare(strict_types=1);
/*
 * Copyright (c) 2021, whatwedo GmbH
 * All rights reserved
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
 * INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
 * WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace whatwedo\CrudBundle\Builder;

use Knp\Menu\ItemInterface;
use Knp\Menu\FactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use whatwedo\CrudBundle\Manager\DefinitionManager;
use whatwedo\CrudBundle\Enum\RouteEnum;

class DefinitionMenuBuilder
{
    protected FactoryInterface $factory;
    protected DefinitionManager $definitionManager;
    protected AuthorizationCheckerInterface $authorizationChecker;
    protected Request $request;

    public function __construct(
        FactoryInterface $factory,
        DefinitionManager $definitionManager,
        AuthorizationCheckerInterface $authorizationChecker,
        RequestStack $requestStack
    ) {
        $this->factory = $factory;
        $this->definitionManager = $definitionManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->request = $requestStack->getCurrentRequest();
    }

    protected function addDefinition(ItemInterface $parent, string $definition, array $options = [], $title = null): ? ItemInterface {
        $definitionObject = $this->definitionManager->getDefinitionByClassName($definition);

        if ($definitionObject
            && $this->authorizationChecker->isGranted(RouteEnum::INDEX, $definitionObject)) {
            if (!$title) {
                $title = $definitionObject::getEntityTitle();
            }

            if (!isset($options['route'])) {
                $options['route'] = $definitionObject::getRouteNamePrefix().'_'.RouteEnum::INDEX;
            }

            $child = $parent->addChild($title, $options);

            if ($this->request) {
                $current = $this->request->attributes->get('_resource') === get_class($definitionObject);
                if ($current
                    && isset($options['routeParameters'])
                    && $options['routeParameters']) {
                    foreach ($options['routeParameters'] as $k => $v) {
                        $current = $current && ($this->request->query->get($k) === $v);
                    }
                }
                $child->setCurrent($current);
            }

            return $child;
        }

        return null;
    }
}
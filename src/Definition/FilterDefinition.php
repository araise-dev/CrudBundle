<?php

declare(strict_types=1);
/*
 * Copyright (c) 2022, whatwedo GmbH
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

namespace araise\CrudBundle\Definition;

use araise\CrudBundle\Builder\DefinitionBuilder;
use araise\CrudBundle\Enums\Page;
use araise\CrudBundle\Enums\PageInterface;
use araise\TableBundle\Entity\Filter;
use araise\TableBundle\Repository\FilterRepository;
use araise\TableBundle\Table\Table;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class FilterDefinition extends AbstractDefinition
{
    public function __construct(
        protected FilterRepository $filterRepository
    ) {
    }

    public static function getEntity(): string
    {
        return Filter::class;
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->filterRepository->getMineQB(self::getQueryAlias());
    }

    public static function getCapabilities(): array
    {
        return [
            Page::INDEX,
            Page::EDIT,
            Page::CREATE,
            Page::DELETE,
            Page::SHOW,
        ];
    }

    public function configureTable(Table $table): void
    {
        parent::configureTable($table);
        $table
            ->addColumn('name')
            ->addColumn('route')
        ;
    }

    public function configureView(DefinitionBuilder $builder, mixed $data): void
    {
        parent::configureView($builder, $data);
        $this->removeAction('create'); // should only be called directly from table filter extension
        $builder
            ->addBlock('base')
            ->addContent('name', null, [
                'help' => false,
            ])
            ->addContent('description', null, [
                'help' => false,
                'form_type' => TextareaType::class,
            ])
        ;
    }

    public function getRedirect(PageInterface $routeFrom, ?object $entity = null): Response
    {
        if ($entity instanceof Filter) {
            $router = $this->container->get(RouterInterface::class);

            return new RedirectResponse($router->generate($entity->getRoute(), array_merge(
                $entity->getArguments(),
                $entity->getConditions()
            )));
        }

        return parent::getRedirect($routeFrom, $entity);
    }
}

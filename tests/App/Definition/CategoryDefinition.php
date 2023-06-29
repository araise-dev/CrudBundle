<?php

declare(strict_types=1);

namespace araise\CrudBundle\Tests\App\Definition;

use araise\CrudBundle\Builder\DefinitionBuilder;
use araise\CrudBundle\Definition\AbstractDefinition;
use araise\CrudBundle\Tests\App\Entity\Category;
use araise\TableBundle\Table\Table;

class CategoryDefinition extends AbstractDefinition
{
    public static function getEntity(): string
    {
        return Category::class;
    }

    /**
     * @param Category $data
     */
    public function configureView(DefinitionBuilder $builder, $data): void
    {
        parent::configureView($builder, $data);

        $builder
            ->addBlock('base')
            ->addContent('name', null, [
            ])
            ->addContent('lft', null, [
            ])
            ->addContent('lvl', null, [
            ])
            ->addContent('rgt', null, [
            ])
        ;
    }

    public function configureTable(Table $table): void
    {
        parent::configureTable($table);
        $table
            ->addColumn('name', null, [
            ])
            ->addColumn('lft', null, [
            ])
            ->addColumn('lvl', null, [
            ])
            ->addColumn('rgt', null, [
            ])
        ;
    }
}

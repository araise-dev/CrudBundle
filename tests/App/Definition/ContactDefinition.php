<?php

declare(strict_types=1);

namespace araise\CrudBundle\Tests\App\Definition;

use araise\CrudBundle\Builder\DefinitionBuilder;
use araise\CrudBundle\Definition\AbstractDefinition;
use araise\CrudBundle\Tests\App\Entity\Contact;
use araise\TableBundle\Table\Table;

class ContactDefinition extends AbstractDefinition
{
    public static function getEntity(): string
    {
        return Contact::class;
    }

    /**
     * @param Contact $data
     */
    public function configureView(DefinitionBuilder $builder, $data): void
    {
        parent::configureView($builder, $data);

        $builder
            ->addBlock('base')
            ->addContent('name', null, [
            ])
        ;
    }

    public function configureTable(Table $table): void
    {
        parent::configureTable($table);
        $table
            ->addColumn('name', null, [
            ])
        ;
    }
}

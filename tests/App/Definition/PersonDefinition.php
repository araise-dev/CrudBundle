<?php

declare(strict_types=1);

namespace araise\CrudBundle\Tests\App\Definition;

use araise\CrudBundle\Builder\DefinitionBuilder;
use araise\CrudBundle\Definition\AbstractDefinition;
use araise\CrudBundle\Enums\Page;
use araise\CrudBundle\Enums\PageInterface;
use araise\CrudBundle\Tests\App\Entity\Person;
use araise\TableBundle\Table\Column;
use araise\TableBundle\Table\Table;

class PersonDefinition extends AbstractDefinition
{
    public static function getEntity(): string
    {
        return Person::class;
    }

    /**
     * @param Person $data
     */
    public function configureView(DefinitionBuilder $builder, $data): void
    {
        parent::configureView($builder, $data);

        $builder
            ->addBlock('base')
            ->addContent('name', null, [])
            ->addContent('jobTitle', null, [])
        ;
    }

    public static function getCapabilities(): array
    {
        return array_merge(parent::getCapabilities(), [Page::EXPORT]);
    }

    public function getFormOptions(PageInterface $page, object $data): array
    {
        if ($page === Page::EDIT) {
            // only check in edit case if the name is not valid
            return [
                'validation_groups' => ['Default', 'check-not-valid'],
            ];
        }

        return [];
    }

    public function configureTable(Table $table): void
    {
        parent::configureTable($table);
        $table
            ->addColumn('name', null, [])
        ;
    }

    public function configureExport(Table $table): void
    {
        $this->configureTable($table);

        $table->addColumn('id', null, [
            Column::OPT_PRIORITY => 200,
        ])
            ->addColumn('jobTitle');
    }
}

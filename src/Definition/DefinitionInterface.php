<?php

declare(strict_types=1);

namespace araise\CrudBundle\Definition;

use araise\CrudBundle\Builder\DefinitionBuilder;
use araise\CrudBundle\Enums\PageInterface;
use araise\CrudBundle\Enums\PageModeInterface;
use araise\CrudBundle\Extension\ExtensionInterface;
use araise\CrudBundle\View\DefinitionView;
use araise\TableBundle\Table\Table;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[Autoconfigure(tags: ['araise_crud.definition'])]
interface DefinitionInterface
{
    public static function supports(mixed $entity): bool;

    public static function getEntityTitleTranslation(mixed $entity = null): string;

    public static function getEntityTitlePluralTranslation(mixed $entity = null): string;

    public static function getAlias(): string;

    public static function hasCapability(PageInterface $page): bool;

    public static function getEntityAlias(): string;

    public static function getRoutePathPrefix(): string;

    public static function getRoutePrefix(): string;

    public static function getRoute(PageInterface $route): string;

    public function getBuilder(): DefinitionBuilder;

    public function getTitle(mixed $entity = null): string;

    public function getLongTitle(?PageInterface $route = null, mixed $entity = null): string;

    public function getMetaTitle(PageInterface $route = null, $entity = null);

    public function getFormAccessorPrefix(): string;

    public function setFormAccessorPrefix(string $formAccessorPrefix): void;

    /**
     * returns capabilities of this definition.
     *
     * Available Options:
     * - list
     * - show
     * - create
     * - edit
     * - delete
     * - batch
     *
     * @return PageInterface[] capabilities
     */
    public static function getCapabilities(): array;

    /**
     * returns options of this definition.
     */
    public function getOptions(): array;

    public function getActions(): array;

    /**
     * returns FQDN of the controller.
     */
    public static function getController(): string;

    /**
     * returns the fqdn of the entity.
     *
     * @return string fqdn of the entity
     */
    public static function getEntity(): string;

    /**
     * returns the query alias to be used.
     *
     * @return string alias
     */
    public static function getQueryAlias(): string;

    public function createEntity(Request $request): mixed;

    /**
     * returns a query builder.
     */
    public function getQueryBuilder(): QueryBuilder;

    /**
     * table configuration.
     */
    public function configureTable(Table $table): void;

    /**
     * table configuration.
     */
    public function configureTableActions(Table $table): void;

    /**
     * table export configuration.
     */
    public function configureExport(Table $table): void;

    /**
     * defines the export file name.
     */
    public function getExportFilename(): string;

    /**
     * check if this definition has specific capability.
    /**
     * get template directory of this definition.
     */
    public function getTemplateDirectory(): string;

    /**
     * returns all layouts to be consumed.
     */
    public function getLayout(): string;

    /**
     * returns a view.
     */
    public function createView(PageInterface $route, ?object $data = null): DefinitionView;

    /**
     * builds the interface.
     */
    public function configureView(DefinitionBuilder $builder, object $data): void;

    /**
     * configure Actions.
     */
    public function configureActions(mixed $data): void;

    public function getRedirect(PageInterface $routeFrom, ?object $entity = null): Response;

    public function ajaxForm(object $entity, PageInterface $page): void;

    public function hasExtension(string $extension): bool;

    public function getExtension(string $extension): ExtensionInterface;

    public function getParentDefinitionProperty(?object $data): ?string;

    public function jsonSearch(string $q): iterable;

    public function getPage(): ?PageInterface;

    public function getPageMode(): ?PageModeInterface;

    public function getBatchActions(): array;

    public function getFormOptions(PageInterface $page, object $data): array;

    public function getSubTables(object $entity): null|Table|array;

    public function getSubTableQueryBuilder(object $entity): null|QueryBuilder|array;

    public function getSubTableDefinition(object $entity): string|array;

    public function showPrevAndNext(): bool;
}

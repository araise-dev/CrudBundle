<?php

declare(strict_types=1);

namespace araise\CrudBundle\Definition;

use araise\CoreBundle\Util\StringConverter;
use araise\CrudBundle\Action\Action;
use araise\CrudBundle\Action\PostAction;
use araise\CrudBundle\Action\SubmitAction;
use araise\CrudBundle\Builder\DefinitionBuilder;
use araise\CrudBundle\Content\AbstractContent;
use araise\CrudBundle\Controller\CrudController;
use araise\CrudBundle\Enums\Page;
use araise\CrudBundle\Enums\PageInterface;
use araise\CrudBundle\Enums\PageMode;
use araise\CrudBundle\Enums\PageModeInterface;
use araise\CrudBundle\Extension\BreadcrumbsExtension;
use araise\CrudBundle\Extension\ExtensionInterface;
use araise\CrudBundle\Extension\JsonSearchExtension;
use araise\CrudBundle\Manager\BlockManager;
use araise\CrudBundle\Manager\DefinitionManager;
use araise\CrudBundle\View\DefinitionView;
use araise\SearchBundle\Repository\IndexRepository;
use araise\TableBundle\DataLoader\DoctrineDataLoader;
use araise\TableBundle\Exporter\TableExporter;
use araise\TableBundle\Extension\FilterExtension;
use araise\TableBundle\Extension\SortExtension;
use araise\TableBundle\Factory\TableFactory;
use araise\TableBundle\Table\Table;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectRepository;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

abstract class AbstractDefinition implements DefinitionInterface, ServiceSubscriberInterface
{
    public const OPT_ACTIONS_OVERFLOW = 'actions_overflow';

    public const OPT_MAIN_FORM_TYPE = 'main_form_type';

    protected ContainerInterface $container;

    protected TranslatorInterface $translator;

    protected array $options = [];

    /**
     * @var \araise\CoreBundle\Action\Action[]
     */
    protected array $actions = [];

    protected array $batchActions = [];

    protected DefinitionBuilder $builder;

    protected Breadcrumbs $breadcrumbs;

    protected array $templates;

    protected string $formAccessorPrefix = '';

    /**
     * @var ExtensionInterface[]
     */
    protected array $extensions;

    public static function getEntity(): string
    {
        throw new \Exception('\araise\CrudBundle\Definition\AbstractDefinition::getEntity must be implemented');
    }

    public static function getEntityTitleTranslation(mixed $entity = null): string
    {
        return 'wwd.'.static::getEntityAlias().'.title';
    }

    public static function getEntityTitlePluralTranslation(mixed $entity = null): string
    {
        return 'wwd.'.static::getEntityAlias().'.title_plural';
    }

    public function createEntity(Request $request): mixed
    {
        $className = static::getEntity();
        $reflection = new \ReflectionClass($className);
        if ($reflection->isInterface()) {
            $metadata = $this->container->get(EntityManagerInterface::class)->getClassMetadata($className);
            $className = $metadata->name;
        }

        return new $className();
    }

    public function getOptions(): array
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        return $this->options = $resolver->resolve($this->options);
    }

    public function setOption(string $key, $value): static
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->options[$key] = $value;
        $this->options = $resolver->resolve($this->options);

        return $this;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            self::OPT_ACTIONS_OVERFLOW => 3,
            self::OPT_MAIN_FORM_TYPE => FormType::class,
        ]);

        $resolver->setAllowedTypes(self::OPT_ACTIONS_OVERFLOW, ['integer']);
        $resolver->setAllowedTypes(self::OPT_MAIN_FORM_TYPE, ['string']);
    }

    public function addAction(string $acronym, array $options = [], string $type = Action::class): static
    {
        if (! isset($options['label'])) {
            $options['label'] = sprintf('wwd.%s.action.%s', self::getEntityAlias(), $acronym);
        }
        $this->actions[$acronym] = new $type($acronym, $options);

        return $this;
    }

    public function getAction(string $acronym): ?\araise\CoreBundle\Action\Action
    {
        if (isset($this->actions[$acronym])) {
            return $this->actions[$acronym];
        }

        return null;
    }

    public function removeAction(string $acronym): static
    {
        if (isset($this->actions[$acronym])) {
            unset($this->actions[$acronym]);
        }

        return $this;
    }

    public function getActions(): array
    {
        uasort(
            $this->actions,
            fn (\araise\CoreBundle\Action\Action $a, \araise\CoreBundle\Action\Action $b) => $a->getOption('priority') <=> $b->getOption('priority')
        );

        return $this->actions;
    }

    /**
     * @deprecated use $table->addBatchAction instead
     */
    public function addBatchAction(string $acronym, array $options = [], string $type = Action::class): static
    {
        if (! isset($options['voter_attribute'])) {
            $options['voter_attribute'] = 'batch_action';
        }
        $this->batchActions[$acronym] = new $type($acronym, $options);

        return $this;
    }

    /**
     * @deprecated use $table->removeBatchAction instead
     */
    public function removeBatchAction(string $acronym): static
    {
        if (isset($this->batchActions[$acronym])) {
            unset($this->batchActions[$acronym]);
        }

        return $this;
    }

    public function getBatchAction(string $acronym): ?\araise\CoreBundle\Action\Action
    {
        if (isset($this->batchActions[$acronym])) {
            return $this->batchActions[$acronym];
        }

        return null;
    }

    public function getBatchActions(): array
    {
        return $this->batchActions;
    }

    public function configureDefinition(): void
    {
    }

    public function configureView(DefinitionBuilder $builder, mixed $data): void
    {
    }

    public function configureTable(Table $table): void
    {
    }

    public function configureExport(Table $table): void
    {
        $this->configureTable($table);
    }

    public function configureTableExporter(Table $table, ?AbstractContent $content = null): void
    {
        if ($content !== null) {
            // default add exporters only on main tables
            return;
        }
        $tableExporter = $this->container->get(TableExporter::class);
        $table->addExporter('table', $tableExporter);
    }

    public function getExportFilename(): string
    {
        $prefix = $this->translator->trans(static::getEntityTitlePluralTranslation());
        $suffix = date('Y-m-d\TH_i_s');

        return sprintf('%s_%s.xlsx', $prefix, $suffix);
    }

    public static function getAlias(): string
    {
        return str_replace(
            ['\\', '_definition', '_bundle'],
            ['_', '', ''],
            strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', static::class))
        );
    }

    public static function getEntityAlias(): string
    {
        return str_replace(
            ['\\', '_definition', '_bundle'],
            ['_', '', ''],
            strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', static::getEntity()))
        );
    }

    public function getTitle(mixed $entity = null): string
    {
        if (!$entity) {
            return '';
        }
        return StringConverter::toString($entity);
    }

    public function getLongTitle(?PageInterface $route = null, mixed $entity = null, bool $withEntityTitle = true): string
    {
        $add = $this->translator->trans('araise_crud.add');
        $delete = $this->translator->trans('araise_crud.delete');
        $edit = $this->translator->trans('araise_crud.edit');
        $show = $this->translator->trans(static::getEntityTitleTranslation($entity));
        $title = '';
        if ($entity) {
            $title = StringConverter::toString($entity);
            $delete .= ': '.$title;
            $edit .= ': '.$title;
            $show .= ': '.$title;
        }

        return match ($route) {
            Page::INDEX => static::getEntityTitlePluralTranslation($entity),
            Page::DELETE => $withEntityTitle ? $delete : $title,
            Page::CREATE => $add,
            Page::EDIT => $withEntityTitle ? $edit : $title,
            Page::SHOW => $withEntityTitle ? $show : $title,
            default => $title,
        };
    }

    public function getMetaTitle(PageInterface $route = null, $entity = null)
    {
        $add = $this->translator->trans('araise_crud.add');
        $delete = $this->translator->trans('araise_crud.delete');
        $edit = $this->translator->trans('araise_crud.edit');
        $show = $this->translator->trans(static::getEntityTitleTranslation($entity));

        return match ($route) {
            Page::INDEX => $this->translator->trans(static::getEntityTitlePluralTranslation($entity)),
            Page::DELETE => $delete,
            Page::CREATE => $add,
            Page::EDIT => $edit,
            Page::SHOW => $show,
        };
    }

    public function getEntityTitle($entity = null)
    {
        return $this->translator->trans(static::getEntityTitleTranslation($entity));
    }

    public static function getCapabilities(): array
    {
        return [
            Page::INDEX,
            Page::SHOW,
            Page::RELOAD,
            Page::CREATE,
            Page::EDIT,
            Page::DELETE,
            Page::JSONSEARCH,
        ];
    }

    public static function hasCapability(PageInterface $page): bool
    {
        return in_array($page, static::getCapabilities(), true);
    }

    public static function getController(): string
    {
        return CrudController::class;
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->getRepository()->createQueryBuilder(static::getQueryAlias());
    }

    /**
     * returns the query alias to be used.
     *
     * @return string alias
     */
    public static function getQueryAlias(): string
    {
        return static::getAlias();
    }

    public function setTemplates(array $templates): void
    {
        $this->templates = $templates;
    }

    public function getBreadcrumbs(): Breadcrumbs
    {
        return $this->getExtension(BreadcrumbsExtension::class)->getBreadcrumbs();
    }

    public function getTemplateDirectory(): string
    {
        return '@araiseCrud/Crud/';
    }

    public function getLayout(): string
    {
        return '@araiseCrud/layout.html.twig';
    }

    public function getBuilder(): DefinitionBuilder
    {
        return $this->builder ?? throw new \RuntimeException('Please call DefinitionInterface::createView before accessing the builder');
    }

    public function createView(PageInterface $route, object $data = null): DefinitionView
    {
        $this->builder = $this->getDefinitionBuilder($data);

        return $this->container->get(DefinitionView::class)->create($this, $route, $data);
    }

    public function getJsonSearchUrl(string $entityClass): string
    {
        $clazz = new \ReflectionClass($entityClass);
        if ($clazz->isInterface()) {
            $metadata = $this->container->get(EntityManagerInterface::class)->getClassMetadata($entityClass);
            $clazz = new \ReflectionClass($metadata->name);
        }
        try {
            $instance = $clazz->newInstance();
        } catch (\Throwable $e) {
            throw new \RuntimeException('Could not automatically detect relation definition for class '.$entityClass.'. Please override getJsonSearchUrl() in '.static::class.' or make the Entity Constructor argument less.', previous: $e);
        }
        /** @var DefinitionInterface $definition */
        $definition = $this
            ->container->get(DefinitionManager::class)
            ->getDefinitionByEntity($instance)
        ;
        if ($definition::hasCapability(Page::JSONSEARCH)) {
            return $this->container->get(RouterInterface::class)
                ->generate($definition::getRoute(Page::JSONSEARCH))
            ;
        }
        $this->container->get(LoggerInterface::class)
            ->warning('you need to enable Page::JSONSEARCH Capability on the "'.get_class($definition).'" definition to allow ajax filtering.')
        ;

        return '';
    }

    public function getLabelFor(?Table $table, string $property): string
    {
        if ($table instanceof Table) {
            foreach ($table->getColumns() as $column) {
                if ($column->getIdentifier() === $property) {
                    $label = $column->getOption('label');
                    if ($label) {
                        return $label;
                    }
                    break;
                }
            }
        }

        foreach ($this->getDefinitionBuilder()->getBlocks() as $block) {
            foreach ($block->getContents() as $content) {
                if ($content->getAcronym() === $property) {
                    $label = $content->getOption('label');
                    if ($label) {
                        return $label;
                    }
                    break;
                }
            }
        }

        return ucfirst($property);
    }

    public function getContent(string $acronym): ?AbstractContent
    {
        foreach ($this->getDefinitionBuilder()->getBlocks() as $block) {
            foreach ($block->getContents() as $content) {
                if ($content->getAcronym() === $acronym) {
                    return $content;
                }
            }
        }

        return null;
    }

    public function getRedirect(PageInterface $routeFrom, ?object $entity = null): Response
    {
        $current = $this->container->get(RequestStack::class)->getCurrentRequest();
        if ($current?->query->get('referer')) {
            return new RedirectResponse($current->query->get('referer'));
        }
        return match ($routeFrom) {
            Page::CREATE, Page::EDIT => new RedirectResponse(
                $this->container->get(RouterInterface::class)->generate(static::getRoute(Page::SHOW), [
                    'id' => $entity->getId(),
                ])
            ),
            default => new RedirectResponse(
                $this->container->get(RouterInterface::class)->generate(static::getRoute(Page::INDEX))
            ),
        };
    }

    public static function getChildRouteAddition(): string
    {
        return static::getQueryAlias();
    }

    public function ajaxForm(object $entity, PageInterface $page): void
    {
    }

    public function jsonSearch(string $q): iterable
    {
        if (! $this->hasExtension(JsonSearchExtension::class)) {
            throw new \Exception('either install araise search bundle or override your jsonSearch function in the definition.');
        }
        $metadata = $this->container->get(EntityManagerInterface::class)->getClassMetadata(static::getEntity());
        $ids = $this->container->get(IndexRepository::class)->search($q, $metadata->getName());

        return $this->getRepository()
            ->createQueryBuilder('xxx')
            ->where('xxx.id IN (:ids)')->setParameter('ids', $ids)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * build breadcrumbs according to route.
     */
    public function buildBreadcrumbs(mixed $entity = null, ?PageInterface $route = null, ?Breadcrumbs $breadcrumbs = null): void
    {
        if (! $this->hasExtension(BreadcrumbsExtension::class)) {
            return;
        }
        if ($breadcrumbs === null) {
            $breadcrumbs = $this->getBreadcrumbs();
        }
        $property = $this->getParentDefinitionProperty($entity);
        if ($entity && $property) {
            $parentEntity = PropertyAccess::createPropertyAccessor()->getValue($entity, $property);
            if ($parentEntity) {
                $this
                    ->container->get(DefinitionManager::class)
                    ->getDefinitionByEntity($parentEntity)
                    ->buildBreadcrumbs($parentEntity, Page::SHOW, $breadcrumbs)
                ;
            }
        }

        if (in_array($route, [Page::INDEX, Page::EDIT, Page::SHOW, Page::CREATE], true)) {
            if (static::hasCapability(Page::INDEX)) {
                $this->getBreadcrumbs()->addRouteItem(static::getEntityTitlePluralTranslation($entity), static::getRoute(Page::INDEX));
            } else {
                $this->getBreadcrumbs()->addItem(static::getEntityTitlePluralTranslation($entity));
            }
        }

        if (in_array($route, [Page::EDIT, Page::SHOW], true)) {
            if (static::hasCapability(Page::SHOW)) {
                $this->getBreadcrumbs()->addRouteItem($this->getLongTitle(Page::SHOW, $entity, withEntityTitle: false), static::getRoute(Page::SHOW), [
                    'id' => $entity->getId(),
                ]);
            } else {
                $this->getBreadcrumbs()->addItem($this->getLongTitle(Page::SHOW, $entity, withEntityTitle: false));
            }
        }

        if ($route === Page::EDIT) {
            if (static::hasCapability(Page::EDIT)) {
                $this->getBreadcrumbs()->addRouteItem($this->getLongTitle(Page::EDIT, $entity), static::getRoute(Page::EDIT), [
                    'id' => $entity->getId(),
                ]);
            } else {
                $this->getBreadcrumbs()->addItem($this->getLongTitle(Page::EDIT, $entity));
            }
        }

        if ($route === Page::CREATE) {
            if (static::hasCapability(Page::CREATE)) {
                $this->getBreadcrumbs()->addRouteItem($this->getLongTitle(Page::CREATE, $entity), static::getRoute(Page::CREATE));
            } else {
                $this->getBreadcrumbs()->addItem($this->getLongTitle(Page::CREATE, $entity));
            }
        }
    }

    public function getTemplateParameters(PageInterface $route, array $parameters = [], mixed $entity = null): array
    {
        return $parameters;
    }

    public function getExtension(string $extension): ExtensionInterface
    {
        if (! $this->hasExtension($extension)) {
            throw new \InvalidArgumentException(sprintf('Extension %s is not enabled. Please configure it first.', $extension));
        }

        return $this->extensions[$extension];
    }

    public function hasExtension(string $extension): bool
    {
        return isset($this->extensions[$extension]);
    }

    public function addExtension(ExtensionInterface $extension): void
    {
        $this->extensions[get_class($extension)] = $extension;
    }

    public static function supports(mixed $entity): bool
    {
        if (is_object($entity)) {
            $entity = $entity::class;
        }

        return is_a($entity, static::getEntity(), true);
    }

    public static function getRoutePathPrefix(): string
    {
        return static::getAlias();
    }

    public static function getRoutePrefix(): string
    {
        return static::getAlias();
    }

    public static function getRoute(PageInterface $route): string
    {
        return static::getRoutePrefix().'_'.$route->toRoute();
    }

    #[Required]
    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    #[Required]
    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }

    public function getParentDefinitionProperty(?object $data): ?string
    {
        return null;
    }

    public function getFormOptions(PageInterface $page, object $data): array
    {
        return [];
    }

    public static function getSubscribedServices(): array
    {
        return [
            BlockManager::class,
            EntityManagerInterface::class,
            DefinitionManager::class,
            DefinitionView::class,
            DefinitionBuilder::class,
            RouterInterface::class,
            IndexRepository::class,
            RequestStack::class,
            LoggerInterface::class,
            TableFactory::class,
            TableExporter::class,
        ];
    }

    public function getPage(): ?PageInterface
    {
        $exploded = explode('_', $this->container->get(RequestStack::class)->getCurrentRequest()->attributes->get('_route'));
        $route = end($exploded);
        foreach (Page::cases() as $page) {
            if ($page->toRoute() === $route) {
                return $page;
            }
        }

        return null;
    }

    public function getPageMode(): ?PageModeInterface
    {
        $page = PageMode::tryFrom($this->container->get(RequestStack::class)->getCurrentRequest()->get('mode', ''));
        return $page ?? PageMode::NORMAL;
    }

    public function getSubTables(object $entity): null|Table|array
    {
        $subQueryBuilders = $this->getSubTableQueryBuilder($entity);
        if ($subQueryBuilders === null) {
            return null;
        }
        if (! is_array($subQueryBuilders)) {
            $subQueryBuilders = [$subQueryBuilders];
        }
        $subTableDefinitions = $this->getSubTableDefinition($entity);
        if (! is_array($subTableDefinitions)) {
            $subTableDefinitions = [$subTableDefinitions];
        }
        if (count($subQueryBuilders) !== count($subTableDefinitions)) {
            throw new \InvalidArgumentException('The number of sub table query builders must match the number of sub table definitions.');
        }
        /** @var TableFactory $tableFactory */
        $tableFactory = $this->container->get(TableFactory::class);
        /** @var DefinitionManager $definitionManager */
        $definitionManager = $this->container->get(DefinitionManager::class);
        $tables = [];
        foreach ($subQueryBuilders as $i => $subQueryBuilder) {
            $table = $tableFactory->create('sub_table_'.$entity->getId().'_'.$i, DoctrineDataLoader::class, [
                'dataloader_options' => [
                    DoctrineDataLoader::OPT_QUERY_BUILDER => $subQueryBuilder,
                ],
            ]);
            $definition = $definitionManager->getDefinitionByClassName($subTableDefinitions[$i]);
            $table->setOption(Table::OPT_DEFINITION, $definition);
            $table->setOption(Table::OPT_TITLE, $definition->getLongTitle(entity: $entity, route: Page::INDEX));
            $table->setOption(Table::OPT_THEME, '@araiseTable/tailwind_2_layout_sub_table.html.twig');
            $table->removeExtension(SortExtension::class);
            $table->getPaginationExtension()->setLimit(0);

            $definition->configureTable($table);
            $tables[] = $table;
        }
        return $tables;
    }

    public function getSubTableQueryBuilder(object $entity): null|QueryBuilder|array
    {
        return null;
    }

    public function getSubTableDefinition(object $entity): string|array
    {
        throw new \RuntimeException('You need to define the Definition for the SubTable!');
    }

    public function configureActions(mixed $data): void
    {
        $dataLoader = $this->container->get(TableFactory::class)->create('index', DoctrineDataLoader::class, [
            'dataloader_options' => [
                DoctrineDataLoader::OPT_QUERY_BUILDER => $this->getQueryBuilder(),
            ],
        ])->getDataLoader();

        if ($this::hasCapability(Page::INDEX)) {
            $this->addAction('index', [
                'label' => 'araise_crud.index',
                'icon' => 'list',
                'visibility' => [Page::CREATE, Page::SHOW, Page::EDIT],
                'route' => static::getRoute(Page::INDEX),
                'attr' => [
                    'class' => 'whatwedo-crud-button--action-neutral',
                ],
                'voter_attribute' => Page::INDEX,
                'priority' => 10,
            ]);
        }

        if ($this::hasCapability(Page::CREATE)) {
            $this->addAction('create', [
                'label' => 'araise_crud.add',
                'icon' => 'plus',
                'visibility' => [Page::INDEX],
                'route' => static::getRoute(Page::CREATE),
                'priority' => 20,
                'voter_attribute' => Page::CREATE,
            ]);
        }

        if ($data) {
            if ($this::hasCapability(Page::SHOW)) {
                $prev = $dataLoader->getPrev($data);
                if ($prev && $this->showPrevAndNext()) {
                    $this->addAction('prev', [
                        'label' => 'araise_crud.prev',
                        'icon' => 'chevron-left',
                        'visibility' => [Page::SHOW],
                        'route' => static::getRoute(Page::SHOW),
                        'route_parameters' => [
                            'id' => $prev->getId(),
                        ],
                        'priority' => 1,
                        'voter_attribute' => Page::SHOW,
                    ]);
                }
                $next = $dataLoader->getNext($data);
                if ($next && $this->showPrevAndNext()) {
                    $this->addAction('next', [
                        'label' => 'araise_crud.next',
                        'icon' => 'chevron-right',
                        'visibility' => [Page::SHOW],
                        'route' => static::getRoute(Page::SHOW),
                        'route_parameters' => [
                            'id' => $next->getId(),
                        ],
                        'priority' => 2,
                        'voter_attribute' => Page::SHOW,
                    ]);
                }
                $this->addAction('view', [
                    'label' => 'araise_crud.view',
                    'icon' => 'eye',
                    'visibility' => [Page::EDIT],
                    'route' => static::getRoute(Page::SHOW),
                    'route_parameters' => [
                        'id' => $data->getId(),
                    ],
                    'priority' => 30,
                    'voter_attribute' => Page::SHOW,
                ]);
            }
            if ($this::hasCapability(Page::EDIT)) {
                $this->addAction('edit', [
                    'label' => 'araise_crud.edit',
                    'icon' => 'pencil',
                    'visibility' => [Page::SHOW],
                    'route' => static::getRoute(Page::EDIT),
                    'route_parameters' => [
                        'id' => $data->getId(),
                    ],
                    'priority' => 40,
                    'voter_attribute' => Page::EDIT,
                ]);
            }

            if ($this::hasCapability(Page::DELETE)) {
                $this->addAction('delete', [
                    'label' => 'araise_crud.delete',
                    'icon' => 'trash',
                    'visibility' => [Page::SHOW, Page::EDIT],
                    'route' => static::getRoute(Page::DELETE),
                    'route_parameters' => [
                        'id' => $data->getId(),
                    ],
                    'attr' => [
                        'class' => 'whatwedo-crud-button--action-danger',
                    ],
                    'priority' => 50,
                    'voter_attribute' => Page::DELETE,
                ], PostAction::class);
            }

            if ($this::hasCapability(Page::EDIT)) {
                $this->addAction('edit_submit', [
                    'label' => 'araise_crud.save',
                    'icon' => 'check-lg',
                    'visibility' => [Page::EDIT],
                    'priority' => 20,
                    'attr' => [
                        'form' => 'crud_main_form',
                    ],
                    'voter_attribute' => Page::EDIT,
                ], SubmitAction::class);
            }

            if ($this::hasCapability(Page::CREATE)) {
                $this->addAction('create_submit', [
                    'label' => 'araise_crud.add',
                    'icon' => 'check-lg',
                    'visibility' => [Page::CREATE],
                    'priority' => 20,
                    'attr' => [
                        'form' => 'crud_main_form',
                    ],
                    'voter_attribute' => Page::CREATE,
                ], SubmitAction::class);
            }
        }
    }

    public function configureTableActions(Table $table): void
    {
        $table->setOption('primary_link', function (object|array $row) {
            if (static::hasCapability(Page::SHOW)) {
                return $this->container->get(RouterInterface::class)->generate(
                    static::getRoute(Page::SHOW),
                    [
                        'id' => $row->getId(),
                    ]
                );
            }

            return null;
        });

        if ($this::hasCapability(Page::SHOW)) {
            $table->addAction('show', [
                'label' => 'araise_crud.view',
                'icon' => 'eye',
                'route' => static::getRoute(Page::SHOW),
                'route_parameters' => fn ($row) => [
                    'id' => $row->getId(),
                ],
                'priority' => 100,
                'voter_attribute' => Page::SHOW,
            ]);
        }

        if ($this::hasCapability(Page::EDIT)) {
            $table->addAction('edit', [
                'label' => 'araise_crud.edit',
                'icon' => 'pencil',
                'route' => static::getRoute(Page::EDIT),
                'route_parameters' => fn ($row) => [
                    'id' => $row->getId(),
                ],
                'priority' => 50,
                'voter_attribute' => Page::EDIT,
            ]);
        }

        if ($this::hasCapability(Page::DELETE)) {
            $table->addAction('delete', [
                'label' => 'araise_crud.delete',
                'icon' => 'trash',
                'route' => static::getRoute(Page::DELETE),
                'route_parameters' => fn ($row) => [
                    'id' => $row->getId(),
                ],
                'priority' => 500,
                'voter_attribute' => Page::DELETE,
            ], PostAction::class);
        }
    }

    public function configureFilters(Table $table): void
    {
        if ($table->hasExtension(FilterExtension::class)) {
            $table->getFilterExtension()
                ->addFiltersAutomatically(
                    $table,
                    [$this, 'getLabelFor'],
                    [$this, 'getJsonSearchUrl'],
                );
        }
    }

    public function getFormAccessorPrefix(): string
    {
        return $this->formAccessorPrefix;
    }

    public function setFormAccessorPrefix(string $formAccessorPrefix): void
    {
        $this->formAccessorPrefix = $formAccessorPrefix;
    }

    public function showPrevAndNext(): bool
    {
        return false;
    }

    protected function getDefinitionBuilder(object|array|null $data = null): DefinitionBuilder
    {
        static $cache;

        if ($cache === null || $data !== null) {
            $builder = $this->container->get(DefinitionBuilder::class);
            $builder->setDefinition($this);
            $this->configureDefinition();
            $this->configureActions($data);
            $this->configureView($builder, $data);

            if ($data === null) {
                $cache = $builder;
            } else {
                return $builder;
            }
        }

        return $cache;
    }

    protected function getRepository(): ObjectRepository
    {
        return $this->container->get(EntityManagerInterface::class)->getRepository(static::getEntity());
    }
}

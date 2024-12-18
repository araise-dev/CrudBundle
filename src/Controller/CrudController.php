<?php

declare(strict_types=1);

namespace araise\CrudBundle\Controller;

use araise\CoreBundle\Exception\FlashBagExecption;
use araise\CrudBundle\Block\Block;
use araise\CrudBundle\Content\RelationContent;
use araise\CrudBundle\Definition\DefinitionInterface;
use araise\CrudBundle\Enums\Page;
use araise\CrudBundle\Enums\PageInterface;
use araise\CrudBundle\Enums\PageMode;
use araise\CrudBundle\Event\CrudEvent;
use araise\CrudBundle\Manager\DefinitionManager;
use araise\CrudBundle\View\DefinitionView;
use araise\TableBundle\DataLoader\DoctrineDataLoader;
use araise\TableBundle\DataLoader\DoctrineTreeDataLoader;
use araise\TableBundle\Entity\TreeInterface;
use araise\TableBundle\Exporter\ExporterInterface;
use araise\TableBundle\Exporter\TableExporter;
use araise\TableBundle\Extension\PaginationExtension;
use araise\TableBundle\Factory\TableFactory;
use araise\TableBundle\Table\Table;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Contracts\Service\Attribute\Required;
use Twig\Environment;

#[AsController]
class CrudController extends AbstractController implements CrudDefinitionControllerInterface
{
    protected ?DefinitionInterface $definition = null;

    protected DefinitionManager $definitionManager;

    protected EventDispatcherInterface $eventDispatcher;

    protected EntityManagerInterface $entityManager;

    protected Environment $twig;

    public function indexAction(TableFactory $tableFactory): Response
    {
        $this->denyAccessUnlessGrantedCrud(Page::INDEX, $this->getDefinition());

        $dataLoader = DoctrineDataLoader::class;
        if (is_subclass_of($this->getDefinition()::getEntity(), TreeInterface::class)) {
            $dataLoader = DoctrineTreeDataLoader::class;
        }
        $options = [
            DoctrineDataLoader::OPT_QUERY_BUILDER => $this->getDefinition()->getQueryBuilder(),
        ];
        if ($dataLoader === DoctrineDataLoader::class) {
            $options[DoctrineDataLoader::OPT_SAVE_LAST_QUERY] = true;
        }

        $table = $tableFactory->create('index', $dataLoader, [
            'dataloader_options' => $options,
        ]);

        $table->setOption('definition', $this->getDefinition());
        $table->setOption('title', $this->getDefinition()->getLongTitle(route: Page::INDEX));
        $this->getDefinition()->configureTableActions($table);
        $this->getDefinition()->configureTable($table);
        $this->getDefinition()->configureFilters($table);
        $this->getDefinition()->configureTableExporter($table);
        $this->getDefinition()->buildBreadcrumbs(null, Page::INDEX);
        $table->setOption(Table::OPT_SUB_TABLE_LOADER, [$this->getDefinition(), 'getSubTables']);

        // @deprecated: remove after deprecation is removed
        // @see AbstractDefinition->addBatchActions()
        foreach ($this->getDefinition()->getBatchActions() as $batchAction) {
            $table->addBatchAction($batchAction->getAcronym(), $batchAction->getOptions(), $batchAction::class);
        }

        return $this->render(
            $this->getTemplate('index.html.twig'),
            $this->getDefinition()->getTemplateParameters(
                Page::INDEX,
                [
                    'view' => $this->getDefinition()->createView(Page::INDEX),
                    'table' => $table,
                    'title' => $this->getDefinition()->getMetaTitle(route: Page::INDEX),
                    'meta' => $this->getDefinition()->getMetaTitle(route: Page::INDEX),
                ]
            )
        );
    }

    public function showAction(Request $request): Response
    {
        $entity = $this->getEntityOr404($request);
        $this->denyAccessUnlessGrantedCrud(Page::SHOW, $entity);

        $this->dispatchEvent(CrudEvent::PRE_SHOW_PREFIX, $entity);

        $this->definition->buildBreadcrumbs($entity, Page::SHOW);

        return $this->render(
            $this->getTemplate('show.html.twig'),
            $this->getDefinition()->getTemplateParameters(
                Page::SHOW,
                [
                    'view' => $this->getDefinition()->createView(Page::SHOW, $entity),
                    'title' => $this->getDefinition()->getTitle($entity),
                    'meta' => $this->getDefinition()->getMetaTitle(Page::SHOW, $entity),
                    '_route' => Page::SHOW,
                ],
                $entity
            )
        );
    }

    public function reloadAction(Request $request): Response
    {
        $entity = $this->getEntityOr404($request);
        $this->denyAccessUnlessGrantedCrud(Page::SHOW, $entity);

        if (! $request->isXmlHttpRequest()) {
            return $this->redirectToCapability(Page::SHOW, array_merge([
                'id' => $entity->getId(),
            ], $request->query->all()));
        }

        $block = $request->attributes->get('block');
        $field = $request->attributes->get('field');

        $this->dispatchEvent(CrudEvent::PRE_SHOW_PREFIX, $entity);

        return $this->render(
            $this->getTemplate('reload.html.twig'),
            $this->getDefinition()->getTemplateParameters(
                Page::RELOAD,
                [
                    'view' => $this->getDefinition()->createView(Page::RELOAD, $entity),
                    'blockAcronym' => $block,
                    'fieldAcronym' => $field,
                    '_route' => Page::RELOAD,
                ],
                $entity
            )
        );
    }

    public function editAction(Request $request): Response
    {
        $entity = $this->getEntityOr404($request);
        $this->denyAccessUnlessGrantedCrud(Page::EDIT, $entity);

        $mode = PageMode::NORMAL;
        if ($request->query->has('mode')) {
            $mode = PageMode::from($request->query->get('mode'));
        }

        $this->dispatchEvent(CrudEvent::PRE_EDIT_FORM_CREATION_PREFIX, $entity);

        $view = $this->getDefinition()->createView(Page::EDIT, $entity);

        $form = $view->getEditForm();

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                $this->dispatchEvent(CrudEvent::PRE_VALIDATE_PREFIX, $entity);
                if ($form->isValid()) {
                    return $this->formSubmittedAndValid($entity, $mode, Page::EDIT);
                }
                throw new FlashBagExecption('error', 'araise_crud.save_error');
            }
        } catch (FlashBagExecption $e) {
            $this->addFlash($e->getFlashType(), $e->getFlashMessage());
        }

        $this->definition->buildBreadcrumbs($entity, Page::EDIT);

        return $this->render(
            $this->getTemplate('edit.html.twig'),
            $this->getDefinition()->getTemplateParameters(
                Page::EDIT,
                [
                    'view' => $view,
                    'title' => $this->getDefinition()->getTitle($entity),
                    'meta' => $this->getDefinition()->getMetaTitle(Page::EDIT, $entity),
                    'form' => $form->createView(),
                    '_route' => Page::EDIT,
                ],
                $entity
            ),
            new Response(null, $form->isSubmitted() && ! $form->isValid() ? 422 : 200)
        );
    }

    public function createAction(Request $request): Response
    {
        $mode = PageMode::NORMAL;
        if ($request->query->has('mode')) {
            $mode = PageMode::from($request->query->get('mode'));
        }

        $this->denyAccessUnlessGrantedCrud(Page::CREATE, $this->getDefinition());

        $entity = $this->getDefinition()->createEntity($request);

        $this->dispatchEvent(CrudEvent::NEW_PREFIX, $entity);

        $view = $this->getDefinition()->createView(Page::CREATE, $entity);

        $this->preselectEntities($request, $view, $entity);

        $this->dispatchEvent(CrudEvent::CREATE_SHOW_PREFIX, $entity);

        $form = $view->getCreateForm();

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                $this->dispatchEvent(CrudEvent::PRE_VALIDATE_PREFIX, $entity);
                if ($form->isValid()) {
                    return $this->formSubmittedAndValid($entity, $mode, Page::CREATE);
                }
                throw new FlashBagExecption('error', 'araise_crud.save_error');
            }
        } catch (FlashBagExecption $e) {
            $this->addFlash($e->getFlashType(), $e->getFlashMessage());
        }

        $this->definition->buildBreadcrumbs($entity, Page::CREATE);

        $template = $this->getTemplate('create.html.twig');
        if ($mode === PageMode::MODAL) {
            $template = $this->getTemplate('create_modal.html.twig');
        }

        return $this->render(
            $template,
            $this->getDefinition()->getTemplateParameters(Page::CREATE, [
                'view' => $view,
                'title' => $this->getDefinition()->getLongTitle(Page::CREATE, $entity),
                'meta' => $this->getDefinition()->getEntityTitle($entity),
                'form' => $form->createView(),
                '_route' => Page::CREATE,
            ], $entity),
            new Response(null, $form->isSubmitted() && ! $form->isValid() ? 422 : 200)
        );
    }

    public function deleteAction(Request $request): Response
    {
        $entity = $this->getEntityOr404($request);
        $this->denyAccessUnlessGrantedCrud(Page::DELETE, $entity);

        try {
            $this->entityManager->remove($entity);
            $this->dispatchEvent(CrudEvent::PRE_DELETE_PREFIX, $entity);
            try {
                $this->entityManager->flush();
            } catch (\Exception) {
                throw new FlashBagExecption('error', 'araise_crud.delete_error');
            }
            $this->dispatchEvent(CrudEvent::POST_DELETE_PREFIX, $entity);
            $this->addFlash('success', 'araise_crud.delete_success');
        } catch (FlashBagExecption $e) {
            $this->addFlash($e->getFlashType(), $e->getFlashMessage());
        }

        return $this->getDefinition()->getRedirect(Page::DELETE, $entity);
    }

    public function exportAction(Request $request, TableFactory $tableFactory, TableExporter $tableExporter): Response
    {
        $this->denyAccessUnlessGrantedCrud(Page::EXPORT, $this->getDefinition());

        $table = $tableFactory
            ->create('index', DoctrineDataLoader::class, [
                Table::OPT_DATALOADER_OPTIONS => [
                    DoctrineDataLoader::OPT_QUERY_BUILDER => $this->getDefinition()->getQueryBuilder(),
                ],
            ]);

        if (
            $request->query->has('definition')
            && $request->query->has('block')
            && $request->query->has('content')
            && $request->query->has('entityId')
        ) {
            $exportDefinitionAlias = $request->query->get('definition');
            $exportBlockAcronym = $request->query->get('block');
            $exportContentAcronym = $request->query->get('content');
            $exportEntityId = $request->query->getInt('entityId');

            $definition = $this->definitionManager->getDefinitionByAlias($exportDefinitionAlias);
            $identifier = sprintf(
                '%s.%s',
                $definition::getQueryAlias(),
                $definition->getQueryBuilder()->getEntityManager()->getClassMetadata($this->getDefinition()::getEntity())->identifier[0]
            );

            try {
                $entity = $definition->getQueryBuilder()
                    ->andWhere($identifier.' = :id')
                    ->setParameter('id', $exportEntityId)
                    ->getQuery()
                    ->getSingleResult();
            } catch (NoResultException | NonUniqueResultException $e) {
                throw new NotFoundHttpException(sprintf('Der gewünschte Datensatz existiert in %s nicht.', $this->getDefinition()->getLongTitle()));
            }

            $view = $definition->createView(Page::SHOW, $entity);
            $blocks = $view->getBlocks()->filter(fn (Block $block) => $block->getAcronym() === $exportBlockAcronym);
            if ($blocks->first() instanceof Block) {
                $relationContent = $blocks->first()->getContent($exportContentAcronym);
                if ($relationContent instanceof RelationContent) {
                    $table = $relationContent->getTable($entity);
                    $this->setDefinition($table->getOption($table::OPT_DEFINITION));
                }
            }
        }

        $this->getDefinition()->configureExport($table);
        $this->getDefinition()->configureFilters($table);
        $this->getDefinition()->configureTableExporter($table);
        if ($request->query->getInt('all', 0) === 1) {
            $table->getExtension(PaginationExtension::class)?->setLimit(0);
        }

        try {
            $exporter = $table->getExporter($request->query->getString('exporter', 'table'));
            if (!$exporter && count($table->getExporters()) > 0) {
                $exporter = $table->getExporter(key($table->getExporters()));
            }
            if (!$exporter instanceof ExporterInterface) {
                throw new FlashBagExecption('error', 'araise_crud.export_error', 'No Exporter found.');
            }
            $spreadsheet = $exporter->createSpreadsheet($table);
            $writer = new Xlsx($spreadsheet);
            $response = new StreamedResponse();
            $response->setCallback(
                function () use ($writer) {
                    $writer->save('php://output');
                }
            );

            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $response->headers->set('Content-Disposition', 'attachment; filename="'.$this->definition->getExportFilename().'"');
        } catch (FlashBagExecption $e) {
            $this->addFlash($e->getFlashType(), $e->getFlashMessage());

            if (isset($entity) && $entity) {
                $response = $this->redirectToRoute($this->definition::getRoute(Page::SHOW), [
                    'id' => $entity->getId(),
                ]);
            } else {
                $response = $this->redirectToRoute($this->definition::getRoute(Page::INDEX));
            }
        }
        return $response;
    }

    public function jsonsearchAction(Request $request): Response
    {
        $array = $this->definition->jsonSearch($request->query->get('q', ''));
        $items = [];
        foreach ($array as $value) {
            $items[] = (object) [
                'id' => $value->getId(),
                'label' => (string) $value,
            ];
        }

        return new JsonResponse((object) [
            'items' => $items,
        ]);
    }

    public static function convertToWindowsCharset(string $string): string
    {
        $charset = mb_detect_encoding(
            $string,
            'UTF-8, ISO-8859-1, ISO-8859-15',
            true
        );

        $string = mb_convert_encoding($string, 'Windows-1252', $charset);

        return $string;
    }

    public function ajaxFormAction(Request $request): Response
    {
        $this->denyAccessUnlessGrantedCrud(Page::AJAXFORM, $this->getDefinition());
        $case = $request->query->get('case', 'create');
        $entity = $this->getDefinition()->createEntity($request);
        if (str_starts_with($case, 'create')) {
            $this->dispatchEvent(CrudEvent::NEW_PREFIX, $entity);
            $view = $this->getDefinition()->createView(Page::CREATE, $entity);
            $this->preselectEntities($request, $view, $entity);
            $this->dispatchEvent(CrudEvent::CREATE_SHOW_PREFIX, $entity);
            $form = $view->getCreateForm();
            $toRenderPage = Page::CREATE;
        } else {
            $view = $this->getDefinition()->createView(Page::EDIT, $entity);
            $form = $view->getEditForm();
            $toRenderPage = Page::EDIT;
        }

        $form->handleRequest($request);
        $data = $form->getData();
        $this->definition->ajaxForm($data, $toRenderPage);
        $view = $this->getDefinition()->createView($toRenderPage, $data);
        $form = $toRenderPage === Page::CREATE ? $view->getCreateForm() : $view->getEditForm();
        $context = [
            'view' => $view,
            'title' => $this->getDefinition()->getLongTitle($toRenderPage, $entity),
            'meta' => $this->getDefinition()->getEntityTitle($entity),
            'form' => $form->createView(),
            '_route' => $toRenderPage,
        ];
        if ($case === 'createmodal') {
            $template = $this->twig->load($this->getTemplate('create_modal.html.twig'));
            $html = $template->render($this->twig->mergeGlobals($context));
        } else {
            $templatePath = $this->getTemplate($toRenderPage === Page::CREATE ? 'create.html.twig' : 'edit.html.twig');
            $template = $this->twig->load($templatePath);
            $html = $template->renderBlock('main', $this->twig->mergeGlobals($context));
        }

        return new Response($html);
    }

    public function setDefinition(?DefinitionInterface $definition): void
    {
        $this->definition = $definition;
    }

    #[Required]
    public function setTwig(Environment $twig): void
    {
        $this->twig = $twig;
    }

    #[Required]
    public function setDefinitionManager(DefinitionManager $definitionManager): void
    {
        $this->definitionManager = $definitionManager;
    }

    #[Required]
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    #[Required]
    public function setEntityManager(EntityManagerInterface $entityManager): void
    {
        $this->entityManager = $entityManager;
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            EventDispatcherInterface::class,
            LoggerInterface::class,
        ]);
    }

    protected function dispatchEvent(string $event, mixed $entity): void
    {
        $this->eventDispatcher->dispatch(new CrudEvent($entity), $event);
        $this->eventDispatcher->dispatch(new CrudEvent($entity), $event.'.'.$this->getDefinition()::getAlias());
    }

    protected function preselectEntities(Request $request, DefinitionView $view, object $entity): void
    {
        if ($request->isMethod('get') || $request->isMethod('post')) {
            // set preselected entities
            $propertyAccessor = PropertyAccess::createPropertyAccessor();

            foreach ($view->getBlocks() as $block) {
                foreach ($block->getContents() as $content) {
                    if ($content->hasOption('preselect_definition')
                        && $content->getOption('preselect_definition')) {
                        $queryParameter = call_user_func([$content->getOption('preselect_definition'), 'getAlias']);

                        if ($queryParameter
                            && $request->query->has($queryParameter)) {
                            $value = $this->entityManager
                                ->getRepository(call_user_func([$content->getOption('preselect_definition'), 'getEntity']))
                                ->find($request->query->getInt($queryParameter));

                            if (! $propertyAccessor->getValue($entity, $content->getOption('accessor_path'))
                                && $request->isMethod('get')) {
                                $propertyAccessor->setValue($entity, $content->getOption('accessor_path'), $value);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * get specific view for a definition.
     */
    protected function getTemplate(string $filename): string
    {
        if ($this->twig->getLoader()->exists($this->getDefinition()->getTemplateDirectory().'/'.$filename)) {
            return $this->getDefinition()->getTemplateDirectory().'/'.$filename;
        }

        return '@araiseCrud/Crud/'.$filename;
    }

    protected function getDefinition(): DefinitionInterface
    {
        return $this->definition;
    }

    /**
     * returns the required entity.
     *
     * @throws NotFoundHttpException
     */
    protected function getEntityOr404(Request $request): mixed
    {
        try {
            return $this->getDefinition()->getQueryBuilder()
                ->andWhere($this->getIdentifierColumn().' = :id')
                ->setParameter('id', $request->attributes->getInt('id'))
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException | NonUniqueResultException $e) {
            throw new NotFoundHttpException(sprintf('Der gewünschte Datensatz existiert in %s nicht.', $this->getDefinition()->getLongTitle()));
        }
    }

    protected function getIdentifierColumn(): string
    {
        return sprintf(
            '%s.%s',
            $this->getDefinition()::getQueryAlias(),
            $this->getDefinition()->getQueryBuilder()->getEntityManager()->getClassMetadata($this->getDefinition()::getEntity())->identifier[0]
        );
    }

    protected function redirectToCapability(PageInterface $page, array $parameters = [], int $status = 302): RedirectResponse
    {
        return $this->redirectToDefinitionObject($this->definition, $page, $parameters, $status);
    }

    protected function redirectToDefinition(string $definitionClass, PageInterface $page, array $parameters = [], int $status = 302): RedirectResponse
    {
        return $this->redirectToDefinitionObject($this->definitionManager->getDefinitionByClassName($definitionClass), $page, $parameters, $status);
    }

    protected function denyAccessUnlessGrantedCrud(mixed $attributes, mixed $subject = null, string $message = 'Access Denied.'): void
    {
        if (! $this->getUser()) {
            return;
        }
        $this->denyAccessUnlessGranted($attributes, $subject, $message);
    }

    /**
     * @override
     */
    protected function denyAccessUnlessGranted(mixed $attribute, mixed $subject = null, string $message = 'Access Denied.'): void
    {
        if (! $this->isGranted($attribute, $subject)) {
            $exception = $this->createAccessDeniedException($message);
            if (is_object($attribute) && enum_exists(get_class($attribute))) {
                $attribute = $attribute->value;
            }
            $exception->setAttributes((string) $attribute);
            $exception->setSubject($subject);

            throw $exception;
        }
    }

    private function formSubmittedAndValid(object $entity, PageMode $mode, PageInterface $page): Response
    {
        $this->dispatchEvent(CrudEvent::POST_VALIDATE_PREFIX, $entity);
        $isCreate = $page === Page::CREATE;
        $isEdit = $page === Page::EDIT;
        if ($isCreate) {
            $this->dispatchEvent(CrudEvent::PRE_CREATE_PREFIX, $entity);
        }
        if ($isEdit) {
            $this->dispatchEvent(CrudEvent::PRE_EDIT_PREFIX, $entity);
        }
        if ($isCreate) {
            $this->entityManager->persist($entity);
        }
        $this->entityManager->flush();
        if ($isCreate) {
            $this->dispatchEvent(CrudEvent::POST_CREATE_PREFIX, $entity);
        }
        if ($isEdit) {
            $this->dispatchEvent(CrudEvent::POST_EDIT_PREFIX, $entity);
        }

        if ($mode === PageMode::MODAL) {
            return new Response('', 200);
        }

        $this->addFlash('success', 'araise_crud.save_success');

        return $this->getDefinition()->getRedirect($page, $entity);
    }

    private function redirectToDefinitionObject(DefinitionInterface $definition, PageInterface $page, array $parameters = [], int $status = 302): RedirectResponse
    {
        $route = $definition::getRoute($page);

        return $this->redirectToRoute($route, $parameters, $status);
    }
}

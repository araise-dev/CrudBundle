<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\View;

use Doctrine\Common\Annotations\Reader;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormRegistryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use whatwedo\CoreBundle\Action\Action;
use whatwedo\CrudBundle\Block\Block;
use whatwedo\CrudBundle\Collection\BlockCollection;
use whatwedo\CrudBundle\Content\AbstractContent;
use whatwedo\CrudBundle\Definition\DefinitionInterface;
use whatwedo\CrudBundle\Enum\Page;
use whatwedo\CrudBundle\Enum\PageInterface;
use whatwedo\CrudBundle\Form\Type\EntityAjaxType;
use whatwedo\CrudBundle\Form\Type\EntityHiddenType;
use whatwedo\CrudBundle\Form\Type\EntityPreselectType;
use whatwedo\CrudBundle\Manager\DefinitionManager;

class DefinitionView
{
    protected ?object $data = null;

    protected PageInterface $route;

    protected ?FormInterface $form = null;

    protected DefinitionInterface $definition;

    protected \ReflectionObject $reflectionObject;

    public function __construct(
        protected DefinitionManager $definitionManager,
        protected FormRegistryInterface $formRegistry,
        protected FormFactoryInterface $formFactory,
        protected RouterInterface $router,
        protected RequestStack $requestStack,
        protected AuthorizationCheckerInterface $authorizationChecker,
        protected Reader $annotationReader,
    ) {
    }

    public function create(DefinitionInterface $definition, PageInterface $route, ?object $data = null): self
    {
        $view = clone $this;
        $view->setDefinition($definition);
        $view->setData($data);
        $view->setRoute($route);

        return $view;
    }

    public function setDefinition(DefinitionInterface $definition)
    {
        $this->definition = $definition;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return object
     */
    public function getData()
    {
        return $this->data;
    }

    public function getRoute(): PageInterface
    {
        return $this->route;
    }

    public function setRoute(PageInterface $route): self
    {
        $this->route = $route;

        return $this;
    }

    public function getActions(): iterable
    {
        return array_filter(
            $this->definition->getActions(),
            fn (Action $action) => in_array($this->route, $action->getOption('visibility'), true)
        );
    }

    /**
     * @return BlockCollection|Block[]
     */
    public function getBlocks(?PageInterface $page = null)
    {
        return $page
            ? $this->definition->getBuilder()->getBlocks()->filterVisibility($page)
            : $this->definition->getBuilder()->getBlocks();
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public function getPath(PageInterface $route, $params = [])
    {
        if ($this->definition->hasCapability($route)) {
            switch ($route) {
                case Page::SHOW:
                case Page::EDIT:
                case Page::DELETE:
                    if (! $this->data) {
                        return 'javascript:alert(\'can\\\'t generate route "' . $route->toRoute() . '" without data\')';
                    }

                    return $this->router->generate(
                        $this->definition::getRoute($route),
                        array_merge([
                            'id' => $this->data->getId(),
                        ], $params)
                    );
                case Page::AJAXFORM:
                    if (! $this->data) {
                        return $this->router->generate(
                            $this->definition::getRoute($route),
                            $params
                        );
                    }

                    return $this->router->generate(
                        $this->definition::getRoute($route),
                        array_merge([
                            'id' => $this->data->getId(),
                        ], $params)
                    );
                case Page::INDEX:
                case Page::BATCH:
                case Page::CREATE:
                    return $this->router->generate(
                        $this->definition::getRoute($route),
                        $params
                    );

                default:
                    return 'javascript:alert(\'can\\\'t generate route "' . $route . '".\')';
            }
        }

        return 'javascript:alert(\'Definition does not have the capability "' . $route . '".\')';
    }

    public function getEditForm(): FormInterface
    {
        if ($this->form instanceof FormInterface) {
            return $this->form;
        }

        $builder = $this->formFactory->createBuilder(
            FormType::class,
            $this->data,
            $this->definition->getFormOptions(Page::EDIT, $this->data)
        );

        foreach ($this->getBlocks() as $block) {
            if (! $block->isVisibleOnEdit()
                || ! $this->authorizationChecker->isGranted($block->getEditVoterAttribute(), $this->data)) {
                continue;
            }

            foreach ($block->getContents() as $content) {
                if (! $content->hasOption('form_type')
                    || ! $content->isVisibleOnEdit()
                    || ! $this->authorizationChecker->isGranted($content->getEditVoterAttribute(), $this->data)) {
                    continue;
                }

                $formType = $this->getFormType($content);

                $builder->add(
                    $content->getAcronym(),
                    $formType,
                    $content->getFormOptions([
                        'required' => $this->isContentRequired($content),
                    ])
                );
            }
        }

        $this->form = $builder->getForm();

        return $this->form;
    }

    public function getCreateForm(): FormInterface
    {
        if ($this->form instanceof FormInterface) {
            return $this->form;
        }

        $builder = $this->formFactory->createBuilder(
            FormType::class,
            $this->data,
            $this->definition->getFormOptions(Page::CREATE, $this->data)
        );

        foreach ($this->getBlocks() as $block) {
            if (! $block->isVisibleOnCreate()
                || ! $this->authorizationChecker->isGranted($block->getCreateVoterAttribute(), $this->data)) {
                continue;
            }

            foreach ($block->getContents() as $content) {
                if (! $content->hasOption('form_type')
                    || ! $content->isVisibleOnCreate()
                    || ! $this->authorizationChecker->isGranted($content->getCreateVoterAttribute(), $this->data)) {
                    continue;
                }

                $formType = $this->getFormType($content);

                $builder->add(
                    $content->getAcronym(),
                    $formType,
                    $content->getFormOptions([
                        'required' => $this->isContentRequired($content),
                    ])
                );
            }
        }

        $this->form = $builder->getForm();

        return $this->form;
    }

    public function hasCapability(PageInterface $route): bool
    {
        return $this->definition->hasCapability($route);
    }

    public function getDefinition(): DefinitionInterface
    {
        return $this->definition;
    }

    protected function isContentRequired(AbstractContent $content): bool
    {
        return $this->formRegistry->getTypeGuesser()
            ->guessRequired($this->getDefinition()::getEntity(), $content->getOption('accessor_path'))
            ->getValue();
    }

    protected function getFormType(AbstractContent $content): ?string
    {
        $formType = $content->getOption('form_type');
        if ($formType === EntityPreselectType::class) {
            if (EntityPreselectType::isValueProvided($this->requestStack->getCurrentRequest(), $content->getFormOptions())) {
                $formType = EntityHiddenType::class;
            } else {
                $formType = EntityAjaxType::class;
            }
        }

        $content->setOption('form_type', $formType);

        return $formType;
    }

    /**
     * @return \ReflectionObject
     */
    protected function getReflectionObject()
    {
        if ($this->reflectionObject === null && $this->data) {
            $this->reflectionObject = new \ReflectionObject($this->data);
        }

        return $this->reflectionObject;
    }
}

<?php
/*
 * Copyright (c) 2016, whatwedo GmbH
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

namespace whatwedo\CrudBundle\View;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Column;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormRegistry;
use Symfony\Component\Form\FormRegistryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\AccessMap;
use Symfony\Component\Security\Http\AccessMapInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use whatwedo\CrudBundle\Collection\BlockCollection;
use whatwedo\CrudBundle\Content\Content;
use whatwedo\CrudBundle\Content\EditableContentInterface;
use whatwedo\CrudBundle\Definition\AbstractDefinition;
use whatwedo\CrudBundle\Definition\DefinitionInterface;
use whatwedo\CrudBundle\Enum\RouteEnum;
use whatwedo\CrudBundle\Form\Type\EntityAjaxType;
use whatwedo\CrudBundle\Form\Type\EntityHiddenType;
use whatwedo\CrudBundle\Form\Type\EntityPreselectType;
use whatwedo\CrudBundle\Manager\DefinitionManager;

/**
 * @author Ueli Banholzer <ueli@whatwedo.ch>
 */
class DefinitionView implements DefinitionViewInterface
{
    /**
     * @var object
     */
    protected $data;

    /**
     * @var DefinitionInterface
     */
    protected $definition;

    /**
     * @var BlockCollection
     */
    protected $blocks;

    /**
     * @var EngineInterface
     */
    protected $templating;

    /**
     * @var FormInterface|null
     */
    protected $form;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var DefinitionManager
     */
    protected $definitionManager;

    /**
     * @var AccessMap $accessMap
     */
    protected $accessMap;

    /**
     * @var AuthorizationChecker $authorizationChecker
     */
    protected $authorizationChecker;

    /**
     * @var array $templates
     */
    protected $templates;

    /**
     * @var array $templateParameters
     */
    protected $templateParameters;

    /**
     * @var AnnotationReader $annotationReader
     */
    protected $annotationReader;

    /**
     * @var \ReflectionObject $reflectionObject
     */
    protected $reflectionObject;

    /**
     * @var Request $request
     */
    protected $request;

    /**
     * @var FormRegistry $formRegistry
     */
    protected $formRegistry;

    /**
     * DefinitionView constructor.
     * @param EngineInterface $templating
     * @param FormFactoryInterface $formFactory
     * @param FormRegistry $formRegistry
     * @param Router $router
     * @param AccessMap $accessMap
     * @param AuthorizationChecker $authorizationChecker
     * @param RequestStack $requestStack
     */
    public function __construct(EngineInterface $templating, FormFactoryInterface $formFactory, FormRegistryInterface $formRegistry, RouterInterface $router, AccessMapInterface $accessMap, AuthorizationCheckerInterface $authorizationChecker, RequestStack $requestStack)
    {
        $this->templating = $templating;
        $this->formFactory = $formFactory;
        $this->router = $router;
        $this->accessMap = $accessMap;
        $this->authorizationChecker = $authorizationChecker;
        $this->annotationReader = new AnnotationReader();
        $this->request = $requestStack->getCurrentRequest();
        $this->formRegistry = $formRegistry;
    }

    /**
     * @param DefinitionManager $definitionManager
     * @required
     */
    public function setDefinitionManager(DefinitionManager $definitionManager)
    {
        $this->definitionManager = $definitionManager;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefinition(DefinitionInterface $definition)
    {
        $this->definition = $definition;
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function setBlocks(BlockCollection $blocks)
    {
        $this->blocks = $blocks;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlocks()
    {
        return $this->blocks;
    }

    /**
     * @param array $templates
     */
    public function setTemplates(array $templates)
    {
        $this->templates = $templates;
    }

    /**
     * @param array $templateParameters
     */
    public function setTemplateParameters(array $templateParameters)
    {
        $this->templateParameters = $templateParameters;
    }

    /**
     * {@inheritdoc}
     */
    public function renderShow($additionalParameters = [])
    {
        return $this->templating->render(
            $this->templates['show'], array_merge([
            'data' => $this->data,
            'helper' => $this,
        ], $additionalParameters, $this->templateParameters));
    }

    /**
     * @param string $value text to be rendered
     * @param Content $content
     * @return string html
     */
    public function linkIt($value, Content $content)
    {
        $entity = $content->getContents($this->data);
        $def = $this->definitionManager->getDefinitionFor($entity);

        if (!is_null($def)) {
            if ($this->authorizationChecker->isGranted(RouteEnum::SHOW, $entity)) {
                $path = $this->router->generate($def::getRoutePrefix() . '_' . RouteEnum::SHOW, ['id' => $entity->getId()]);

                $granted = false;
                if (!$this->authorizationChecker->isGranted('IS_AUTHENTICATED_ANONYMOUSLY')) {
                    // if the user is not authenticated, we link it because a
                    // login form is shown if the user tries to access the resource
                    // otherwise there would happens a InsufficientAuthenticationException
                    $fakeRequest = Request::create($path, 'GET');
                    list($roles, $channel) = $this->accessMap->getPatterns($fakeRequest);
                    foreach ($roles as $role) {
                        $granted = $granted || $this->authorizationChecker->isGranted($role);
                    }
                } else {
                    $granted = true;
                }

                if ($granted) {
                    return sprintf('<a href="%s">%s</a>', $path, $value);
                }
            }
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function renderEdit($additionalParameters = [])
    {
        return $this->templating->render($this->templates['edit'], array_merge([
            'form' => $this->getEditForm()->createView(),
            'helper' => $this,
        ], $additionalParameters, $this->templateParameters));
    }

    /**
     * {@inheritdoc}
     */
    public function renderCreate($additionalParameters = [])
    {
        return $this->templating->render($this->templates['create'], array_merge([
            'form' => $this->getCreateForm()->createView(),
            'helper' => $this,
        ], $additionalParameters, $this->templateParameters));
    }

    /**
     * @param $route
     * @param array $params
     * @return string
     */
    public function getPath($route, $params = [])
    {
        if ($this->definition->hasCapability($route)) {
            switch($route) {
                case RouteEnum::SHOW:
                case RouteEnum::EDIT:
                case RouteEnum::DELETE:
                    if (!$this->data) {
                        return 'javascript:alert(\'can\\\'t generate route "' . $route . '" without data\')';
                    }

                    return $this->router->generate(sprintf('%s_%s', $this->definition->getRoutePrefix(), $route),
                        array_merge([
                            'id' => $this->data->getId(),
                            ], $params)
                    );
                case RouteEnum::AJAX:
                    if (!$this->data) {
                        return $this->router->generate(sprintf('%s_%s', $this->definition->getRoutePrefix(), $route),
                            $params
                        );
                    }
                    return $this->router->generate(sprintf('%s_%s', $this->definition->getRoutePrefix(), $route),
                        array_merge([
                            'id' => $this->data->getId(),
                        ], $params)
                    );
                case RouteEnum::INDEX:
                case RouteEnum::BATCH:
                case RouteEnum::CREATE:
                    return $this->router->generate(sprintf('%s_%s', $this->definition->getRoutePrefix(), $route),
                        $params
                    );

                default:
                    return 'javascript:alert(\'can\\\'t generate route "' . $route . '".\')';
            }
        }

        return 'javascript:alert(\'Definition does not have the capability "' . $route . '".\')';
    }

    /**
     * @param EditableContentInterface $content
     * @return boolean
     */
    protected function isContentRequired($content)
    {
        $reflectionObject = $this->getReflectionObject();
        if (!is_null($reflectionObject)) {
            foreach ($reflectionObject->getProperties() as $property) {
                if ($property->getName() === $content->getAcronym()) {
                    $notNullAnnotation = $this->annotationReader->getPropertyAnnotation($property, NotNull::class);
                    $notBlankAnnotation = $this->annotationReader->getPropertyAnnotation($property, NotBlank::class);
                    $columnAnnotation = $this->annotationReader->getPropertyAnnotation($property, Column::class);
                    if (!is_null($columnAnnotation) && ($columnAnnotation->type === 'boolean' || $columnAnnotation->type === 'bool')) {
                        return false;
                    }
                    return !is_null($notNullAnnotation) || !is_null($notBlankAnnotation);
                }
            }
        }
        return false;
    }

    /**
     * @param Content $content
     * @return string
     */
    protected function getFormType(Content $content)
    {
        $formType = $content->getFormType();
        if ($formType === EntityPreselectType::class) {
            if (EntityPreselectType::isValueProvided($this->request, $content->getFormOptions())) {
                $formType = EntityHiddenType::class;
            } else {
                $formType = EntityAjaxType::class;
            }
        }
        $content->setOption('form_type', $formType);
        return $formType;
    }

    /**
     * @return null|FormInterface
     */
    public function getEditForm()
    {
        if ($this->form instanceof FormInterface) {
            return $this->form;
        }

        $builder = $this->formFactory->createBuilder(FormType::class, $this->data, []);

        foreach ($this->getBlocks() as $block) {
            if (!$block->isVisibleOnEdit()) {
                continue;
            }

            foreach ($block->getContents() as $content) {
                if (!$content->isVisibleOnEdit()) {
                    continue;
                }

                if ($content instanceof EditableContentInterface) {
                    $formType = $this->getFormType($content);

                    $builder->add(
                        $content->getAcronym(),
                        $formType,
                        $content->getFormOptions([ 'required' => $this->isContentRequired($content) ])
                    );
                }
            }
        }

        $this->form = $builder->getForm();

        return $this->form;
    }

    /**
     * @return null|FormInterface
     */
    public function getCreateForm()
    {
        if ($this->form instanceof FormInterface) {
            return $this->form;
        }

        $builder = $this->formFactory->createBuilder(FormType::class, $this->data, []);

        foreach ($this->getBlocks() as $block) {
            if (!$block->isVisibleOnCreate()) {
                continue;
            }

            foreach ($block->getContents() as $content) {
                if (!$content->isVisibleOnCreate()) {
                    continue;
                }
                if ($content instanceof EditableContentInterface) {
                    $formType = $this->getFormType($content);

                    $builder->add(
                        $content->getAcronym(),
                        $formType,
                        $content->getFormOptions([ 'required' => $this->isContentRequired($content) ])
                    );
                }
            }
        }

        $this->form = $builder->getForm();

        return $this->form;
    }

    /**
     * @param bool $onlylisten
     * @return string
     */
    public function getAjaxListen($onlylisten = false)
    {
        $data = $this->definition->addAjaxOnChangeListener();
        if ($onlylisten)
        {
            $data = array_filter($data, function($item) {
                return $item == AbstractDefinition::AJAX_LISTEN;
            });
        }
        $ret = '[';
        $i = 0;
        foreach ($data as $key => $item)
        {
            $ret .= '\'' . $key . '\'';
            if ($i != count($data) - 1){
                $ret .= ',';
            }
            $i++;
        }
        $ret .= ']';
        return $ret;
    }

    /**
     * @param $route
     * @return bool
     */
    public function hasCapability($route)
    {
        return $this->definition->hasCapability($route);
    }

    /**
     * @return DefinitionInterface
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * @return \ReflectionObject
     */
    protected function getReflectionObject()
    {
        if (is_null($this->reflectionObject) && $this->data) {
            $this->reflectionObject = new \ReflectionObject($this->data);
        }
        return $this->reflectionObject;
    }

    /**
     * @param string $class
     * @param string $property
     * @return null|\Symfony\Component\Form\Guess\Guess|\Symfony\Component\Form\Guess\TypeGuess
     */
    public function guessType($class, $property)
    {
        return $this->formRegistry->getTypeGuesser()->guessType($class, $property);
    }
}

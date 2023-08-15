<?php

declare(strict_types=1);

namespace araise\CrudBundle\Formatter;

use araise\CoreBundle\Formatter\DefaultFormatter;
use araise\CrudBundle\Enums\Page;
use araise\CrudBundle\Manager\DefinitionManager;
use Coduo\ToString\StringConverter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\RouterInterface;

class CrudDefaultFormatter extends DefaultFormatter
{
    public function __construct(
        protected DefinitionManager $definitionManager,
        protected RouterInterface $router,
        protected Security $security
    ) {
    }

    public function getHtml(mixed $value): string
    {
        if (is_object($value)) {
            try {
                $definition = $this->definitionManager->getDefinitionByEntity($value);
                if ($definition::hasCapability(Page::SHOW) && $this->security->isGranted(Page::SHOW, $value)) {
                    return sprintf(
                        '<a href="%s">%s</a>',
                        $this->router->generate($definition::getRoute(Page::SHOW), [
                            'id' => $value->getId(),
                        ]),
                        new StringConverter($value)
                    );
                }
            } catch (\InvalidArgumentException $e) {
                // no definition found ...
            }
        }

        return parent::getHtml($value);
    }
}

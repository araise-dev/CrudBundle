<?php

declare(strict_types=1);

namespace araise\CrudBundle\Formatter;

use Symfony\Component\Routing\RouterInterface;
use araise\CoreBundle\Formatter\DefaultFormatter;
use araise\CrudBundle\Enums\Page;
use araise\CrudBundle\Manager\DefinitionManager;

class CrudDefaultFormatter extends DefaultFormatter
{
    public function __construct(
        protected DefinitionManager $definitionManager,
        protected RouterInterface $router
    ) {
    }

    public function getHtml(mixed $value): string
    {
        if (is_object($value)) {
            try {
                $definition = $this->definitionManager->getDefinitionByEntity($value);
                if ($definition::hasCapability(Page::SHOW)) {
                    return sprintf(
                        '<a href="%s">%s</a>',
                        $this->router->generate($definition::getRoute(Page::SHOW), [
                            'id' => $value->getId(),
                        ]),
                        (string) $value
                    );
                }
            } catch (\InvalidArgumentException $e) {
                // no definition found ...
            }
        }

        return parent::getHtml($value);
    }
}

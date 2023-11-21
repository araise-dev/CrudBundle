<?php

declare(strict_types=1);

namespace araise\CrudBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class CrudTurboExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        protected bool $turboEnabled
    ) {
    }

    public function getGlobals(): array
    {
        return [
            'araise_crud_turbo_enabled' => $this->turboEnabled,
        ];
    }
}

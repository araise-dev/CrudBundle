<?php

declare(strict_types=1);

namespace araise\CrudBundle\Controller;

use araise\CrudBundle\Definition\DefinitionInterface;

interface CrudDefinitionControllerInterface
{
    public function setDefinition(DefinitionInterface $definition): void;
}

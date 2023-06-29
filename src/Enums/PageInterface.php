<?php

declare(strict_types=1);

namespace araise\CrudBundle\Enums;

interface PageInterface
{
    public function toRoute(): string;
}

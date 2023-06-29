<?php

declare(strict_types=1);

namespace araise\CrudBundle\Test\Data;

class IndexData extends AbstractData
{
    public static function new(): AbstractData
    {
        return new self();
    }
}

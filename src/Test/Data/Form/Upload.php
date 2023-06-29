<?php

declare(strict_types=1);

namespace araise\CrudBundle\Test\Data\Form;

class Upload
{
    private string $path;

    private string $field;

    public function __construct(string $path, string $fieldName = 'file')
    {
        $this->path = $path;
        $this->field = $fieldName;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getField(): string
    {
        return $this->field;
    }
}

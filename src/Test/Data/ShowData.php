<?php

declare(strict_types=1);

namespace araise\CrudBundle\Test\Data;

class ShowData extends AbstractData
{
    public function __construct(
        protected bool $skip = false,
        protected array $queryParameters = [],
        protected string $entityId = '1',
        protected int $expectedStatusCode = 200,
        protected bool $followRedirects = false,
        protected ?\Closure $assertCallback = null,
    ) {
        parent::__construct(
            $this->skip,
            $this->queryParameters,
            $this->expectedStatusCode,
            $this->followRedirects,
            $this->assertCallback
        );
    }

    public static function new(): self
    {
        return new self();
    }

    public function setEntityId(string $entityId): self
    {
        $this->entityId = $entityId;

        return $this;
    }

    public function getEntityId(): string
    {
        return $this->entityId;
    }
}

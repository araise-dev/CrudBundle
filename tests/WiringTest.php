<?php

declare(strict_types=1);

namespace araise\CrudBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use araise\CrudBundle\Manager\DefinitionManager;
use araise\CrudBundle\Tests\App\Manager\UnwantedManager;

class WiringTest extends KernelTestCase
{
    public function testServiceWiring(): void
    {
        $serviceClass = DefinitionManager::class;
        $this->assertInstanceOf(
            $serviceClass,
            self::getContainer()->get($serviceClass)
        );
    }

    public function testUnwantedAreRemoved(): void
    {
        $serviceNotFoundException = null;
        try {
            self::getContainer()->get(UnwantedManager::class);
            self::assertFalse(true, 'UnwantedManager should not be wired');
        } catch (ServiceNotFoundException $serviceNotFoundException) {
        }
        self::assertNotNull($serviceNotFoundException);
    }
}

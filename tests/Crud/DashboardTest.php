<?php

declare(strict_types=1);

namespace araise\CrudBundle\Tests\Crud;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class DashboardTest extends WebTestCase
{
    public function testDashboard(): void
    {
        $client = static::createClient();
        $admin = self::getContainer()->get(UserProviderInterface::class)->loadUserByIdentifier('admin');
        $client->loginUser($admin);
        $client->request('GET', '/dashboard');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Hello 👋');
    }
}

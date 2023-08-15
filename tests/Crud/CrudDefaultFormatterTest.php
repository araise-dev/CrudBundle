<?php

declare(strict_types=1);

namespace araise\CrudBundle\Tests\Crud;

use araise\CrudBundle\Enums\Page;
use araise\CrudBundle\Test\Data\ShowData;
use araise\CrudBundle\Tests\App\Definition\PersonDefinition;
use araise\CrudBundle\Tests\App\Factory\PersonFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class CrudDefaultFormatterTest extends AbstractCrudTest
{
    public function getTestData(): array
    {
        return [
            Page::SHOW->name => [
                'with-link-link' => [
                    ShowData::new()->setAssertCallback(function (Crawler $crawler, AbstractBrowser $browser) {
                        $text = trim($crawler->filter('#wwd-crud-block-base-content-category-content')->html());
                        self::assertStringStartsWith('<a href="/araise_crud_tests_app_category/1">category_prefix.phpunit', $text);
                    }),
                ],
            ],
        ];
    }

    protected function getDefinitionClass(): string
    {
        return PersonDefinition::class;
    }

    protected function getBrowser(): KernelBrowser
    {
        $client = parent::getBrowser();
        $superAdmin = self::getContainer()->get(UserProviderInterface::class)->loadUserByIdentifier('super_admin');
        $client->loginUser($superAdmin);
        return $client;
    }

    protected function setUp(): void
    {
        $this->getBrowser();
        PersonFactory::createMany(2);
    }
}

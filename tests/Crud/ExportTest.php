<?php

declare(strict_types=1);

/*
 * Copyright (c) 2022, whatwedo GmbH
 * All rights reserved
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
 * INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
 * WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace araise\CrudBundle\Tests\Crud;

use araise\CrudBundle\Enums\Page;
use araise\CrudBundle\Test\Data\CreateData;
use araise\CrudBundle\Test\Data\ExportData;
use araise\CrudBundle\Test\Data\ShowData;
use araise\CrudBundle\Tests\App\Definition\PersonDefinition;
use araise\CrudBundle\Tests\App\Factory\PersonFactory;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Zenstruck\Foundry\Test\Factories;

class ExportTest extends AbstractCrudTest
{
    use Factories;

    public function getTestData(): array
    {
        return [
            Page::EXPORT->name => [
                'simple' => [
                    ExportData::new(),
                ],
            ],
            Page::CREATE->name => [
                [
                    CreateData::new()->setSkip(true),
                ],
            ],
            Page::SHOW->name => [
                'no-link' => [
                    ShowData::new()->setAssertCallback(function (Crawler $crawler, AbstractBrowser $browser) {
                        $text = trim($crawler->filter('#wwd-crud-block-base-content-category-content')->html());
                        self::assertStringStartsWith('category_prefix.phpunit', $text);
                    }),
                ],
            ],
        ];
    }

    protected function getDefinitionClass(): string
    {
        return PersonDefinition::class;
    }

    protected function setUp(): void
    {
        $this->getBrowser();
        PersonFactory::createMany(10);
    }
}

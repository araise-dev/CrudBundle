<?php

declare(strict_types=1);
/*
 * Copyright (c) 2021, whatwedo GmbH
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

namespace araise\CrudBundle\Test;

use araise\CrudBundle\Definition\DefinitionInterface;
use araise\CrudBundle\Enums\Page;
use araise\CrudBundle\Manager\DefinitionManager;
use araise\CrudBundle\Test\Data\AbstractData;
use araise\CrudBundle\Test\Data\CreateData;
use araise\CrudBundle\Test\Data\EditData;
use araise\CrudBundle\Test\Data\ExportData;
use araise\CrudBundle\Test\Data\Form\Upload;
use araise\CrudBundle\Test\Data\IndexData;
use araise\CrudBundle\Test\Data\ShowData;
use araise\TableBundle\DataLoader\DoctrineDataLoader;
use araise\TableBundle\DataLoader\DoctrineTreeDataLoader;
use araise\TableBundle\Entity\TreeInterface;
use araise\TableBundle\Factory\TableFactory;
use araise\TableBundle\Table\Column;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\Routing\RouterInterface;

abstract class AbstractCrudTest extends WebTestCase
{
    protected ?KernelBrowser $client = null;

    /**
     * @dataProvider indexData()
     */
    public function testIndex(IndexData $indexData): void
    {
        $this->setUpTestIndex($indexData);
        if (! $this->getDefinition()::hasCapability(Page::INDEX)) {
            $this->markTestSkipped('no index capability, skip test');
        }

        if ($indexData->isSkip()) {
            $this->markTestSkipped('index Test Skipped');
        }

        $url = $this->getRouter()->generate(
            $this->getDefinition()::getRoute(Page::INDEX),
            $indexData->getQueryParameters()
        );

        $this->getBrowser()->followRedirects($indexData->isFollowRedirects());

        $crawler = $this->getBrowser()->request('GET', $url);

        $this->assertStatusCode($indexData->getExpectedStatusCode());

        if ($indexData->getAssertCallback() && is_callable($indexData->getAssertCallback())) {
            $indexData->getAssertCallback()($crawler, $this->getBrowser());
        }
    }

    public function indexData()
    {
        $testData = $this->getTestData();
        if (isset($testData[Page::INDEX->name])) {
            return $testData[Page::INDEX->name];
        }

        return [
            'default' => [
                IndexData::new(),
            ],
        ];
    }

    /**
     * @dataProvider indexSortData()
     */
    public function testIndexSort(IndexData $indexData): void
    {
        $this->setUpTestIndexSort($indexData);
        if (! $this->getDefinition()::hasCapability(Page::INDEX)) {
            $this->markTestSkipped('no index capability, skip test');
        }

        if ($indexData->isSkip()) {
            $this->markTestSkipped('index Test Skipped');
        }

        $this->getBrowser()->followRedirects($indexData->isFollowRedirects());

        $this->getBrowser()->request('GET', $this->getRouter()->generate(
            $this->getDefinition()::getRoute(Page::INDEX),
            $indexData->getQueryParameters()
        ));
        $this->assertStatusCode($indexData->getExpectedStatusCode());
    }

    public function indexSortData()
    {
        $testData = $this->getTestData();

        if (isset($testData[Page::INDEX->name])) {
            $testData = $testData[Page::INDEX->name];
        }

        $testData = [
            [
                IndexData::new(),
            ],
        ];

        $sortTestData = [];

        $tableFactory = self::getContainer()->get(TableFactory::class);
        $dataLoader = DoctrineDataLoader::class;
        if (is_subclass_of($this->getDefinition()::getEntity(), TreeInterface::class)) {
            $dataLoader = DoctrineTreeDataLoader::class;
        }

        $table = $tableFactory->create('index', $dataLoader, [
            'dataloader_options' => [
                DoctrineDataLoader::OPT_QUERY_BUILDER => $this->getDefinition()->getQueryBuilder(),
            ],
        ]);

        $this->getDefinition()->configureTable($table);

        if ($table->getSortExtension()) {
            $sortExtension = $table->getSortExtension();

            foreach ($table->getColumns() as $column) {
                if ($column->getOption(Column::OPT_SORTABLE)) {
                    $sortQueryData = $sortExtension->getOrderParameters($column, 'asc');
                    foreach ($testData as $testKey => $testItem) {
                        /** @var IndexData $indexData */
                        $indexData = clone $testItem[0];
                        $indexData->setQueryParameters(
                            array_merge(
                                $indexData->getQueryParameters(),
                                $sortQueryData
                            )
                        );
                        $sortTestData[$column->getIdentifier() . '-asc'] = [$indexData];
                    }
                }
            }
        }

        return $sortTestData;
    }

    /**
     * @dataProvider exportData()
     */
    public function testExport(ExportData $indexData): void
    {
        $this->setUpTestExport($indexData);
        if (! $this->getDefinition()::hasCapability(Page::EXPORT)) {
            $this->markTestSkipped('no export capability, skip test');
        }

        if ($indexData->isSkip()) {
            $this->markTestSkipped('index Test Skipped');
        }

        $this->getBrowser()->followRedirects($indexData->isFollowRedirects());

        $crawler = $this->getBrowser()->request('GET', $this->getRouter()->generate(
            $this->getDefinition()::getRoute(Page::EXPORT),
            $indexData->getQueryParameters()
        ));
        $this->assertStatusCode($indexData->getExpectedStatusCode());

        if ($indexData->getAssertCallback() && is_callable($indexData->getAssertCallback())) {
            $indexData->getAssertCallback()($crawler, $this->getBrowser());
        }
    }

    public function exportData()
    {
        $testData = $this->getTestData();
        if (isset($testData[Page::EXPORT->name])) {
            return $testData[Page::EXPORT->name];
        }

        return [
            'default' => [
                ExportData::new(),
            ],
        ];
    }

    /**
     * @dataProvider showData()
     */
    public function testShow(ShowData $showData): void
    {
        $this->setUpTestShow($showData);
        if (! $this->getDefinition()::hasCapability(Page::SHOW)) {
            $this->markTestSkipped('no show capability, skip test');
        }

        if ($showData->isSkip()) {
            $this->markTestSkipped('show Test Skipped');
        }

        $this->getBrowser()->followRedirects($showData->isFollowRedirects());

        $crawler = $this->getBrowser()->request('GET', $this->getRouter()->generate(
            $this->getDefinition()::getRoute(Page::SHOW),
            array_merge([
                'id' => $showData->getEntityId(),
            ], $showData->getQueryParameters())
        ));
        $this->assertStatusCode($showData->getExpectedStatusCode());

        if ($showData->getAssertCallback() && is_callable($showData->getAssertCallback())) {
            $showData->getAssertCallback()($crawler, $this->getBrowser());
        }
    }

    public function showData()
    {
        $testData = $this->getTestData();
        if (isset($testData[Page::SHOW->name])) {
            return $testData[Page::SHOW->name];
        }

        return [
            'id-1' => [
                ShowData::new(),
            ],
        ];
    }

    /**
     * @dataProvider editData()
     */
    public function testEdit(?EditData $editData): void
    {
        if ($editData === null) {
            $this->markTestSkipped('no data provided for edit, skip test');
        }

        $this->setUpTestEdit($editData);
        if (! $this->getDefinition()::hasCapability(Page::EDIT)) {
            $this->markTestSkipped('no edit capability, skip test');
        }

        if ($editData->isSkip()) {
            $this->markTestSkipped('show Test Skipped');
        }

        $editLink = $this->getRouter()->generate(
            $this->getDefinition()::getRoute(Page::EDIT),
            array_merge([
                'id' => $editData->getEntityId(),
            ], $editData->getQueryParameters())
        );
        $crawler = $this->getBrowser()->request('GET', $editLink);
        $this->assertSame(200, $this->getBrowser()->getResponse()->getStatusCode());

        $form = $crawler->filter('#crud_main_form')->form([], 'POST');
        $this->fillForm($form, $editData->getFormData());

        $this->getBrowser()->followRedirects($editData->isFollowRedirects());

        if ($editData->getAssertBeforeSendCallback() && is_callable($editData->getAssertBeforeSendCallback())) {
            $editData->getAssertBeforeSendCallback()($crawler, $this->getBrowser());
        }

        $this->getBrowser()->submit($form);
        $this->assertStatusCode($editData->getExpectedStatusCode());

        if ($editData->getAssertCallback() && is_callable($editData->getAssertCallback())) {
            $editData->getAssertCallback()($crawler, $this->getBrowser());
        }
    }

    public function editData()
    {
        $testData = $this->getTestData();
        if (isset($testData[Page::EDIT->name])) {
            return $testData[Page::EDIT->name];
        }

        return [
            'default' => [
                null,
            ],
        ];
    }

    /**
     * @dataProvider createData()
     */
    public function testCreate(?CreateData $createData): void
    {
        if ($createData === null) {
            $this->markTestSkipped('no data provided for create, skip test');
        }

        $this->setUpTestCreate($createData);
        if (! $this->getDefinition()::hasCapability(Page::CREATE)) {
            $this->markTestSkipped('no edit capability, skip test');
        }

        if ($createData->isSkip()) {
            $this->markTestSkipped('create Test Skipped');
        }

        $createLink = $this->getRouter()->generate(
            $this->getDefinition()::getRoute(Page::CREATE),
            $createData->getQueryParameters()
        );
        $crawler = $this->getBrowser()->request('GET', $createLink);
        $this->assertSame(200, $this->getBrowser()->getResponse()->getStatusCode());
        $form = $crawler->filter('#crud_main_form')->form([], 'POST');
        $this->fillForm($form, $createData->getFormData());

        $this->getBrowser()->followRedirects($createData->isFollowRedirects());

        if ($createData->getAssertBeforeSendCallback() && is_callable($createData->getAssertBeforeSendCallback())) {
            $createData->getAssertBeforeSendCallback()($crawler, $this->getBrowser());
        }

        $this->getBrowser()->submit($form);

        $this->assertStatusCode($createData->getExpectedStatusCode());

        if ($createData->getAssertCallback() && is_callable($createData->getAssertCallback())) {
            $createData->getAssertCallback()($crawler, $this->getBrowser());
        }
    }

    public function createData()
    {
        $testData = $this->getTestData();
        if (isset($testData[Page::CREATE->name])) {
            return $testData[Page::CREATE->name];
        }

        return [
            'default' => [
                null,
            ],
        ];
    }

    /**
     * @return array<string, array<int|string, array<int, AbstractData>>>
     */
    public function getTestData(): array
    {
        return [];
    }

    abstract protected function getDefinitionClass(): string;

    protected function getBrowser(): KernelBrowser
    {
        if (! $this->client) {
            static::ensureKernelShutdown();
            $this->client = static::createClient();
            $this->client->followRedirects(false);
        }

        return $this->client;
    }

    protected function getDefinition(): DefinitionInterface
    {
        /** @var DefinitionManager $manager */
        $manager = self::getContainer()->get(DefinitionManager::class);

        return $manager->getDefinitionByClassName($this->getDefinitionClass());
    }

    protected function getRouter(): RouterInterface
    {
        return self::getContainer()->get(RouterInterface::class);
    }

    protected function fillForm(Form $form, $formData)
    {
        foreach ($formData as $field => $value) {
            if ($value instanceof Upload) {
                $form['form[' . $field . '][' . $value->getField() . ']']->upload($value->getPath());
            } else {
                $form['form[' . $field . ']'] = $value;
            }
        }
    }

    protected function assertStatusCode(int $expectedStatusCode, string $message = 'Status Code is not as expected!'): void
    {
        self::assertSame($expectedStatusCode, $this->getBrowser()->getResponse()->getStatusCode(), $message);
    }

    protected function setUpTestIndex(IndexData $indexData)
    {
        $this->setUpTest($indexData, 'index');
    }

    protected function setUpTestIndexSort(IndexData $indexData)
    {
        $this->setUpTest($indexData, 'indexSort');
    }

    protected function setUpTestExport(ExportData $indexData)
    {
        $this->setUpTest($indexData, 'export');
    }

    protected function setUpTestShow(ShowData $showData)
    {
        $this->setUpTest($showData, 'show');
    }

    protected function setUpTestEdit(EditData $editData)
    {
        $this->setUpTest($editData, 'edit');
    }

    protected function setUpTestCreate(CreateData $createData)
    {
        $this->setUpTest($createData, 'create');
    }

    protected function setUpTest(AbstractData $createData, string $testType)
    {
    }
}

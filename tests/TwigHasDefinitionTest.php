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

namespace araise\CrudBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;
use araise\CrudBundle\Tests\App\Entity\Person;
use araise\CrudBundle\Tests\App\Factory\PersonFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class TwigHasDefinitionTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    public function testHasDefinitionPerson()
    {
        /** @var Environment $twigEnvironment */
        $twigEnvironment = self::getContainer()->get(Environment::class);

        $this->assertSame('1', $twigEnvironment->render(
            'twig/wwd_crud_entity_has_definition.html.twig',
            [
                'person' => PersonFactory::createOne()->object(),
            ]
        ));
    }

    public function testHasDefinitionSomeThingElse()
    {
        /** @var Environment $twigEnvironment */
        $twigEnvironment = self::getContainer()->get(Environment::class);

        $this->assertSame('', $twigEnvironment->render(
            'twig/wwd_crud_entity_has_definition.html.twig',
            [
                'person' => 'someThing',
            ]
        ));
    }

    public function testHasDefinitionClass()
    {
        /** @var Environment $twigEnvironment */
        $twigEnvironment = self::getContainer()->get(Environment::class);

        $this->assertSame('1', $twigEnvironment->render(
            'twig/wwd_crud_entity_has_definition.html.twig',
            [
                'person' => Person::class,
            ]
        ));
    }
}

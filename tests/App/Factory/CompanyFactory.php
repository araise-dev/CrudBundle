<?php

declare(strict_types=1);

namespace araise\CrudBundle\Tests\App\Factory;

use araise\CrudBundle\Tests\App\Entity\Company;
use araise\CrudBundle\Tests\App\Repository\CompanyRepository;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;

/**
 * @method static        Company|Proxy createOne(array $attributes = [])
 * @method static        Company[]|Proxy[] createMany(int $number, $attributes = [])
 * @method static        Company|Proxy find($criteria)
 * @method static        Company|Proxy findOrCreate(array $attributes)
 * @method static        Company|Proxy first(string $sortedField = 'id')
 * @method static        Company|Proxy last(string $sortedField = 'id')
 * @method static        Company|Proxy random(array $attributes = [])
 * @method static        Company|Proxy randomOrCreate(array $attributes = [])
 * @method static        Company[]|Proxy[] all()
 * @method static        Company[]|Proxy[] findBy(array $attributes)
 * @method static        Company[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static        Company[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static        CompanyRepository|ProxyRepositoryDecorator repository()
 * @method Company|Proxy create($attributes = [])
 */
final class CompanyFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Company::class;
    }

    protected function defaults(): array
    {
        return [
            'name' => self::faker()->company(),
            'city' => self::faker()->city(),
            'country' => self::faker()->country(),
            'taxIdentificationNumber' => self::faker()->numerify(self::faker()->countryCode().'###.####.###.#.###.##'),
        ];
    }
}

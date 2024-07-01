<?php

declare(strict_types=1);

namespace araise\CrudBundle\Tests\App\Factory;

use araise\CrudBundle\Tests\App\Entity\Person;
use araise\CrudBundle\Tests\App\Repository\PersonRepository;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;

/**
 * @method static       Person|Proxy createOne(array $attributes = [])
 * @method static       Person[]|Proxy[] createMany(int $number, $attributes = [])
 * @method static       Person|Proxy find($criteria)
 * @method static       Person|Proxy findOrCreate(array $attributes)
 * @method static       Person|Proxy first(string $sortedField = 'id')
 * @method static       Person|Proxy last(string $sortedField = 'id')
 * @method static       Person|Proxy random(array $attributes = [])
 * @method static       Person|Proxy randomOrCreate(array $attributes = [])
 * @method static       Person[]|Proxy[] all()
 * @method static       Person[]|Proxy[] findBy(array $attributes)
 * @method static       Person[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static       Person[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static       PersonRepository|ProxyRepositoryDecorator repository()
 * @method Person|Proxy create($attributes = [])
 */
final class PersonFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Person::class;
    }

    protected function defaults(): array
    {
        return [
            'name' => self::faker()->name(),
            'jobTitle' => self::faker()->jobTitle(),
            'category' => CategoryFactory::new(),
        ];
    }
}

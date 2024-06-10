<?php

declare(strict_types=1);

namespace araise\CrudBundle\Tests\App\Factory;

use araise\CrudBundle\Tests\App\Entity\Category;
use araise\CrudBundle\Tests\App\Repository\CategoryRepository;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;

/**
 * @method static         Category|Proxy createOne(array $attributes = [])
 * @method static         Category[]|Proxy[] createMany(int $number, $attributes = [])
 * @method static         Category|Proxy find($criteria)
 * @method static         Category|Proxy findOrCreate(array $attributes)
 * @method static         Category|Proxy first(string $sortedField = 'id')
 * @method static         Category|Proxy last(string $sortedField = 'id')
 * @method static         Category|Proxy random(array $attributes = [])
 * @method static         Category|Proxy randomOrCreate(array $attributes = [])
 * @method static         Category[]|Proxy[] all()
 * @method static         Category[]|Proxy[] findBy(array $attributes)
 * @method static         Category[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static         Category[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static         CategoryRepository|ProxyRepositoryDecorator repository()
 * @method Category|Proxy create($attributes = [])
 */
final class CategoryFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Category::class;
    }

    protected function defaults(): array
    {
        return [
            'name' => 'category_prefix.phpunit'.self::faker()->company(),
        ];
    }
}

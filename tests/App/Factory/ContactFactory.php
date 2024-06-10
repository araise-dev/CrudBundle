<?php

declare(strict_types=1);

namespace araise\CrudBundle\Tests\App\Factory;

use araise\CrudBundle\Tests\App\Entity\Contact;
use araise\CrudBundle\Tests\App\Repository\ContactRepository;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;

/**
 * @method static        Contact|Proxy createOne(array $attributes = [])
 * @method static        Contact[]|Proxy[] createMany(int $number, $attributes = [])
 * @method static        Contact|Proxy find($criteria)
 * @method static        Contact|Proxy findOrCreate(array $attributes)
 * @method static        Contact|Proxy first(string $sortedField = 'id')
 * @method static        Contact|Proxy last(string $sortedField = 'id')
 * @method static        Contact|Proxy random(array $attributes = [])
 * @method static        Contact|Proxy randomOrCreate(array $attributes = [])
 * @method static        Contact[]|Proxy[] all()
 * @method static        Contact[]|Proxy[] findBy(array $attributes)
 * @method static        Contact[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static        Contact[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static        ContactRepository|ProxyRepositoryDecorator repository()
 * @method Contact|Proxy create($attributes = [])
 */
final class ContactFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Contact::class;
    }

    protected function defaults(): array
    {
        return [
            'name' => self::faker()->name(),
            'company' => CompanyFactory::randomOrCreate(),
        ];
    }
}

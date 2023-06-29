<?php

declare(strict_types=1);

namespace araise\CrudBundle\Tests\App\Repository;

use araise\CrudBundle\Tests\App\Entity\Person;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Person|null   find($id, $lockMode = null, $lockVersion = null)
 * @method Person|null   findOneBy(array $criteria, array $orderBy = null)
 * @method array<Person> findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method Person        findOneByName(string $name)
 */
final class PersonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Person::class);
    }
}

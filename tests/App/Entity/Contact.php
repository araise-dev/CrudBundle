<?php

declare(strict_types=1);

namespace araise\CrudBundle\Tests\App\Entity;

use araise\SearchBundle\Annotation\Index;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'contact')]
#[ORM\Entity(repositoryClass: 'araise\CrudBundle\Tests\App\Repository\ContactRepository')]
class Contact implements \Stringable
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[Index]
    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    private ?string $name = null;

    /**
     * Many Groups have Many Members.
     */
    #[ORM\ManyToOne(targetEntity: 'Company', inversedBy: 'contacts')]
    private Company $company;

    public function __construct(?Company $company = null)
    {
        if ($company) {
            $this->company = $company;
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName($name): void
    {
        $this->name = $name;
    }

    public function getCompany(): Company
    {
        return $this->company;
    }

    public function setCompany(Company $company): void
    {
        $this->company = $company;
    }

    public function __toString(): string
    {
        return (string) $this->name;
    }
}

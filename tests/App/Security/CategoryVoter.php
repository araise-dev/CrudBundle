<?php

declare(strict_types=1);

namespace araise\CrudBundle\Tests\App\Security;

use araise\CrudBundle\Enums\Page;
use araise\CrudBundle\Tests\App\Entity\Category;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CategoryVoter extends Voter
{
    public function __construct(
        private readonly Security $security
    ) {
    }

    protected function supports(mixed $attribute, mixed $subject): bool
    {
        return $attribute === Page::SHOW && $subject instanceof Category;
    }

    protected function voteOnAttribute(mixed $attribute, mixed $subject, TokenInterface $token): bool
    {
        return $this->security->isGranted('ROLE_SUPER_ADMIN');
    }
}

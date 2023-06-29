<?php

declare(strict_types=1);

namespace araise\CrudBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class CrudEvent extends Event
{
    public const PRE_CREATE_PREFIX = 'araise_crud.pre_create';

    public const POST_CREATE_PREFIX = 'araise_crud.post_create';

    public const PRE_DELETE_PREFIX = 'araise_crud.pre_delete';

    public const POST_DELETE_PREFIX = 'araise_crud.post_delete';

    public const PRE_EDIT_FORM_CREATION_PREFIX = 'araise_crud.pre_edit_form_creation';

    public const PRE_EDIT_PREFIX = 'araise_crud.pre_edit';

    public const POST_EDIT_PREFIX = 'araise_crud.post_edit';

    public const PRE_VALIDATE_PREFIX = 'araise_crud.pre_validate';

    public const POST_VALIDATE_PREFIX = 'araise_crud.post_validate';

    public const PRE_SHOW_PREFIX = 'araise_crud.pre_show';

    public const CREATE_SHOW_PREFIX = 'araise_crud.create_show';

    public const NEW_PREFIX = 'araise_crud.new';

    public function __construct(
        protected object $entity
    ) {
    }

    public function getEntity(): object|array
    {
        return $this->entity;
    }
}

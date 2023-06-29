<?php

declare(strict_types=1);

namespace araise\CrudBundle\Action;

use araise\CoreBundle\Action\Action as BaseAction;

class Action extends BaseAction
{
    use CrudActionTrait;

    public function __construct(string $acronym, array $options)
    {
        $this->setDefaultOptions();
        parent::__construct($acronym, $options);
    }
}

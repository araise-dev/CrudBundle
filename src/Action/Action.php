<?php

declare(strict_types=1);

namespace araise\CrudBundle\Action;

use araise\CoreBundle\Action\Action as BaseAction;
use araise\CrudBundle\Enums\Page;

class Action extends BaseAction
{
    /**
     * Defines the visibility of the content. Available options are the on the definition defined Capabilities.
     * Defaults to <code>[Page::SHOW, Page::EDIT, Page::CREATE]</code>
     * Accepts: <code>array</code>.
     */
    public const OPT_VISIBILITY = 'visibility';

    public const OPT_DEFAULT_VISIBILITY = [Page::INDEX, Page::SHOW, Page::EDIT, Page::CREATE];

    public function __construct(string $acronym, array $options)
    {
        $this->defaultOptions[self::OPT_VISIBILITY] = self::OPT_DEFAULT_VISIBILITY;
        parent::__construct($acronym, $options);
    }
}
